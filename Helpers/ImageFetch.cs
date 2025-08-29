using System.Net.Http;

namespace Jas.Helpers
{
    public static class ImageFetch
    {
        // Sdílený HttpClient (bez opakovaného vytváření soketů)
        private static readonly HttpClient _http = new HttpClient
        {
            Timeout = TimeSpan.FromSeconds(5)
        };

        /// <summary>
        /// Ověří dostupnost obrázku přes HEAD na absolutní URL (ideálně /images/... endpoint).
        /// Pokud je to tvůj /images handler, zajistí i nakopírování lokálně.
        /// </summary>
        public static async Task<bool> EnsureImageLocalAsync(string absoluteImgUrl, CancellationToken ct = default)
        {
            if (string.IsNullOrWhiteSpace(absoluteImgUrl))
                return false;

            // musí být absolutní URL (http/https)
            if (!Uri.TryCreate(absoluteImgUrl, UriKind.Absolute, out var uri) ||
                (uri.Scheme != Uri.UriSchemeHttp && uri.Scheme != Uri.UriSchemeHttps))
            {
                throw new ArgumentException("absoluteImgUrl musí být absolutní http/https URL.", nameof(absoluteImgUrl));
            }

            using var req = new HttpRequestMessage(HttpMethod.Head, uri);
            using var resp = await _http.SendAsync(req, HttpCompletionOption.ResponseHeadersRead, ct);
            return resp.IsSuccessStatusCode; // 200 => existuje (a u /images je teď i lokálně)
        }

        /// <summary>
        /// Ověří dostupnost pro relativní URL (např. "/images/...") – absolutní URL složí z HttpContext (scheme+host).
        /// </summary>
        public static Task<bool> EnsureImageLocalAsync(HttpContext httpContext, string relativeImgUrl, CancellationToken ct = default)
        {
            if (httpContext == null) throw new ArgumentNullException(nameof(httpContext));
            if (string.IsNullOrWhiteSpace(relativeImgUrl)) return Task.FromResult(false);

            var req = httpContext.Request;
            // ořež případné dvojité lomítko
            var path = relativeImgUrl.StartsWith("/") ? relativeImgUrl : "/" + relativeImgUrl;
            var absolute = $"{req.Scheme}://{req.Host}{path}";
            return EnsureImageLocalAsync(absolute, ct);
        }
    }
}
