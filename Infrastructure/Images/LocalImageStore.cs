using System;
using System.Collections.Concurrent;
using System.IO;
using System.Net;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Threading;
using System.Threading.Tasks;
using Jas.Application.Abstractions;
using Microsoft.AspNetCore.Hosting;
using Microsoft.Extensions.Logging;
using Microsoft.Extensions.Options;

namespace Jas.Infrastructure.Images;

/// <summary>
/// Domain-based image store:
/// - Vstup přijímá: absolutní URL (http/https), "host[/port]/path?query", i "/images/host[/port]/path?query"
/// - Lokálně ukládá do: wwwroot/{RootSubfolder}/{host[_port]}/{path}  (bez query)
/// - Remote URL se skládá z https://{host[:port]}/{path}{?query} (s volitelným http fallbackem)
/// - Revalidace: TTL + SWR, kontrola změny podle Content-Length (HEAD/Range)
/// </summary>
public class LocalImageStore : IImageStore
{
    private readonly string _root;
    private readonly ImageStoreOptions _options;
    private readonly IHttpClientFactory _httpClientFactory;
    private readonly ILogger<LocalImageStore> _log;

    // SWR/TTL bookkeeping
    private readonly ConcurrentDictionary<string, DateTime> _lastCheckedUtc = new();
    private readonly ConcurrentDictionary<string, Task> _inflight = new();
    private readonly SemaphoreSlim _revalSemaphore;

    public LocalImageStore(
        IWebHostEnvironment env,
        IOptions<ImageStoreOptions> options,
        IHttpClientFactory httpClientFactory,
        ILogger<LocalImageStore> log)
    {
        _options = options.Value;
        _httpClientFactory = httpClientFactory;
        _log = log;

        var webRoot = env.WebRootPath ?? "wwwroot";
        _root = Path.GetFullPath(Path.Combine(webRoot, _options.RootSubfolder));
        Directory.CreateDirectory(_root);

        var max = _options.MaxConcurrentRevalidations <= 0 ? Environment.ProcessorCount : _options.MaxConcurrentRevalidations;
        _revalSemaphore = new SemaphoreSlim(max, max);
    }

    // -------- helpers --------

    private static string TrimImagesPrefix(string input)
    {
        var t = (input ?? string.Empty).Trim();
        if (t.StartsWith("/images/", StringComparison.OrdinalIgnoreCase))
            return t.Substring("/images/".Length);
        return t.TrimStart('/');
    }

    private static string SanitizeAuthorityForPath(string authority)
    {
        // kvůli Windows FS – ":" v host:port nahradíme "_"
        return authority.Replace(':', '_');
    }

    private static string ToHttpFallbackUrl(string httpsUrl)
    {
        if (Uri.TryCreate(httpsUrl, UriKind.Absolute, out var uri) &&
            uri.Scheme.Equals(Uri.UriSchemeHttps, StringComparison.OrdinalIgnoreCase))
        {
            var b = new UriBuilder(uri)
            {
                Scheme = Uri.UriSchemeHttp,
                // -1 → použije default port pro http (odstraní :443)
                Port = uri.IsDefaultPort ? -1 : uri.Port
            };
            return b.Uri.ToString();
        }
        return httpsUrl.Replace("https://", "http://", StringComparison.OrdinalIgnoreCase);
    }

    public string MapPath(string relativePath)
    {
        // Odstranit případný prefix /images/ a query
        var safeRel = (relativePath ?? string.Empty).Replace('\\', '/').Trim();
        safeRel = TrimImagesPrefix(safeRel);

        var q = safeRel.IndexOf('?', StringComparison.Ordinal);
        if (q >= 0) safeRel = safeRel.Substring(0, q);

        var combined = Path.Combine(_root, safeRel.Replace('/', Path.DirectorySeparatorChar));
        var full = Path.GetFullPath(combined);

        var rootWithSep = _root.EndsWith(Path.DirectorySeparatorChar.ToString(), StringComparison.Ordinal)
            ? _root
            : _root + Path.DirectorySeparatorChar;

        if (!full.StartsWith(rootWithSep, StringComparison.OrdinalIgnoreCase))
            throw new InvalidOperationException("Neplatná cesta k obrázku.");

        return full;
    }

    public bool Exists(string relativePath) => File.Exists(MapPath(relativePath));

    public Task<Stream?> OpenReadAsync(string relativePath, CancellationToken ct = default)
    {
        var full = MapPath(relativePath);
        if (!File.Exists(full)) return Task.FromResult<Stream?>(null);
        Stream s = new FileStream(full, FileMode.Open, FileAccess.Read, FileShare.Read);
        return Task.FromResult<Stream?>(s);
    }

    public async Task<bool> TryEnsureLocalAsync(string relativePath, CancellationToken ct = default)
    {
        // Vstup: absolutní URL, "host[/port]/path?query", nebo "/images/host[/port]/path?query"
        if (!TryResolveRemoteUrl(relativePath, out var remoteUrl, out var localRel))
            return false;

        var full = MapPath(localRel);

        if (File.Exists(full))
        {
            await MaybeRevalidateAsync(localRel, full, remoteUrl, ct);
            return true;
        }

        return await DownloadAsync(remoteUrl, full, ct);
    }

    public string ProductPath(string regNumber)
    {
        var basePath = _options.ProductBasePath?.TrimEnd('/') ?? "/images";
        return $"{basePath}/{regNumber}/{regNumber}_1.jpg";
    }
    
    /// <summary>
    /// Převede vstup (absolutní URL / "host[/port]/path?query" / "/images/host[/port]/path?query") na (remoteUrl, localRel).
    /// localRel neobsahuje query; remoteUrl query zachovává.
    /// </summary>
    private bool TryResolveRemoteUrl(string input, out string remoteUrl, out string localRel)
    {
        remoteUrl = string.Empty;
        localRel = string.Empty;

        var raw = (input ?? string.Empty).Replace('\\', '/').Trim();

        // 1) Absolutní URL (ponecháme přesně, včetně query)
        if (Uri.TryCreate(raw, UriKind.Absolute, out var abs) &&
            (abs.Scheme == Uri.UriSchemeHttp || abs.Scheme == Uri.UriSchemeHttps))
        {
            var authority = abs.IsDefaultPort ? abs.Host : $"{abs.Host}:{abs.Port}";
            var safeAuthority = SanitizeAuthorityForPath(authority);
            var path = abs.AbsolutePath.TrimStart('/');

            remoteUrl = abs.ToString();                 // https://host[:port]/path?query
            localRel  = $"{safeAuthority}/{path}";      // wwwroot/images/{host[_port]}/path
            return true;
        }

        // 2) "/images/host[/port]/path?query" NEBO "host[/port]/path?query"
        var norm = TrimImagesPrefix(raw);              // odstraní případné /images/
        var qIdx = norm.IndexOf('?', StringComparison.Ordinal);
        var pathPart = qIdx >= 0 ? norm.Substring(0, qIdx) : norm;
        var queryPart = qIdx >= 0 ? norm.Substring(qIdx) : string.Empty; // včetně '?'

        var slash = pathPart.IndexOf('/');
        if (slash <= 0)
            return false; // chybí host/path

        var hostSeg = pathPart.Substring(0, slash);   // může být i "host:port" nebo "localhost"
        var rest    = pathPart.Substring(slash + 1);

        var safeHost = SanitizeAuthorityForPath(hostSeg);
        localRel = $"{safeHost}/{rest}";

        // Remote URL: preferuj https; případný http fallback řeší DownloadAsync
        remoteUrl = $"https://{hostSeg}/{rest}{queryPart}";
        return true;
    }

    // ---------- Revalidace (TTL + SWR + deduplikace) ----------

    private async Task MaybeRevalidateAsync(string localRel, string fullPath, string remoteUrl, CancellationToken ct)
    {
        var ttl = _options.RefreshTtl;
        if (ttl is null || ttl.Value <= TimeSpan.Zero) return;

        var now = DateTime.UtcNow;
        var last = _lastCheckedUtc.AddOrUpdate(localRel, _ => DateTime.MinValue, (_, existing) => existing);
        if (now - last < ttl.Value) return;

        _lastCheckedUtc[localRel] = now;

        if (_options.ServeStaleWhileRevalidate)
        {
            _ = StartDedupedRevalidation(localRel, fullPath, remoteUrl);
        }
        else
        {
            try
            {
                await RevalidateCoreAsync(localRel, fullPath, remoteUrl, CancellationToken.None);
            }
            catch (Exception ex)
            {
                _log.LogDebug(ex, "Inline revalidace selhala pro {Path}", localRel);
            }
        }
    }

    private Task StartDedupedRevalidation(string localRel, string fullPath, string remoteUrl)
    {
        return _inflight.GetOrAdd(localRel, key => Task.Run(async () =>
        {
            try
            {
                await RevalidateCoreAsync(localRel, fullPath, remoteUrl, CancellationToken.None);
            }
            catch (Exception ex)
            {
                _log.LogDebug(ex, "SWR revalidace selhala pro {Path}", localRel);
            }
            finally
            {
                _inflight.TryRemove(localRel, out _);
            }
        }));
    }

    private async Task RevalidateCoreAsync(string localRel, string fullPath, string remoteUrl, CancellationToken ct)
    {
        await _revalSemaphore.WaitAsync(ct);
        try
        {
            if (!_options.RevalidateByRemoteSize)
                return;

            var remoteLen = await GetRemoteContentLengthAsync(remoteUrl, ct);
            if (remoteLen is null) return;

            var localLen = new FileInfo(fullPath).Length;
            if (localLen != remoteLen.Value)
            {
                _log.LogInformation(
                    "Změna velikosti detekována pro {Path} (lokálně {Local} B, remote {Remote} B). Stahuji...",
                    localRel, localLen, remoteLen.Value);

                await DownloadAsync(remoteUrl, fullPath, ct);
            }
        }
        finally
        {
            _revalSemaphore.Release();
        }
    }

    // ---------- Síťové pomocné metody (s http fallback) ----------

    private async Task<long?> GetRemoteContentLengthAsync(string remoteUrl, CancellationToken ct)
    {
        var http = _httpClientFactory.CreateClient(nameof(LocalImageStore));

        try
        {
            // 1) HEAD s identity (bez komprese)
            using (var head = new HttpRequestMessage(HttpMethod.Head, remoteUrl))
            {
                head.Headers.AcceptEncoding.Clear();
                head.Headers.AcceptEncoding.Add(new StringWithQualityHeaderValue("identity"));

                using var resp = await http.SendAsync(head, HttpCompletionOption.ResponseHeadersRead, ct);
                if (resp.IsSuccessStatusCode)
                    return resp.Content.Headers.ContentLength;

                if (resp.StatusCode != HttpStatusCode.MethodNotAllowed &&
                    resp.StatusCode != HttpStatusCode.NotImplemented)
                {
                    // jiná chyba – zkusíme fallback přes Range
                }
            }

            // 2) Range 0-0
            using (var get = new HttpRequestMessage(HttpMethod.Get, remoteUrl))
            {
                get.Headers.Range = new RangeHeaderValue(0, 0);
                get.Headers.AcceptEncoding.Clear();
                get.Headers.AcceptEncoding.Add(new StringWithQualityHeaderValue("identity"));

                using var resp = await http.SendAsync(get, HttpCompletionOption.ResponseHeadersRead, ct);
                if (resp.StatusCode == HttpStatusCode.PartialContent || resp.IsSuccessStatusCode)
                {
                    var cr = resp.Content.Headers.ContentRange;
                    if (cr?.Length is long total) return total;

                    return resp.Content.Headers.ContentLength;
                }
            }
        }
        catch (Exception ex) when (_options.AllowHttpFallback && remoteUrl.StartsWith("https://", StringComparison.OrdinalIgnoreCase))
        {
            var httpUrl = ToHttpFallbackUrl(remoteUrl);
            _log.LogDebug(ex, "HEAD/Range na {Remote} selhal, zkouším fallback {Fallback}", remoteUrl, httpUrl);
            return await GetRemoteContentLengthAsync(httpUrl, ct);
        }

        return null;
    }

    private async Task<bool> DownloadAsync(string remoteUrl, string fullPath, CancellationToken ct)
    {
        var http = _httpClientFactory.CreateClient(nameof(LocalImageStore));

        try
        {
            Directory.CreateDirectory(Path.GetDirectoryName(fullPath)!);

            using var resp = await http.GetAsync(remoteUrl, HttpCompletionOption.ResponseHeadersRead, ct);
            if (!resp.IsSuccessStatusCode)
            {
                // https → http fallback (pokud povoleno)
                if (_options.AllowHttpFallback && remoteUrl.StartsWith("https://", StringComparison.OrdinalIgnoreCase))
                {
                    var httpUrl = ToHttpFallbackUrl(remoteUrl);
                    using var resp2 = await http.GetAsync(httpUrl, HttpCompletionOption.ResponseHeadersRead, ct);
                    if (!resp2.IsSuccessStatusCode) return false;
                    return await WriteAtomicAsync(resp2, fullPath, ct);
                }

                return false;
            }

            return await WriteAtomicAsync(resp, fullPath, ct);
        }
        catch (OperationCanceledException) { throw; }
        catch (Exception ex) when (_options.AllowHttpFallback && remoteUrl.StartsWith("https://", StringComparison.OrdinalIgnoreCase))
        {
            var httpUrl = ToHttpFallbackUrl(remoteUrl);
            _log.LogDebug(ex, "GET na {Remote} selhal, zkouším fallback {Fallback}", remoteUrl, httpUrl);
            try
            {
                using var resp = await http.GetAsync(httpUrl, HttpCompletionOption.ResponseHeadersRead, ct);
                if (!resp.IsSuccessStatusCode) return false;
                return await WriteAtomicAsync(resp, fullPath, ct);
            }
            catch (OperationCanceledException) { throw; }
            catch (Exception ex2)
            {
                _log.LogWarning(ex2, "Fallback GET také selhal: {Url}", httpUrl);
                return false;
            }
        }
        catch (Exception ex)
        {
            _log.LogWarning(ex, "Stažení obrázku selhalo: {Remote}", remoteUrl);
            return false;
        }
    }

    private static async Task<bool> WriteAtomicAsync(HttpResponseMessage resp, string fullPath, CancellationToken ct)
    {
        var tmp = fullPath + ".tmp";
        await using (var input = await resp.Content.ReadAsStreamAsync(ct))
        await using (var output = new FileStream(tmp, FileMode.Create, FileAccess.Write, FileShare.Read))
            await input.CopyToAsync(output, ct);

        if (File.Exists(fullPath))
            File.Replace(tmp, fullPath, null);
        else
            File.Move(tmp, fullPath);
        return true;
    }
}
