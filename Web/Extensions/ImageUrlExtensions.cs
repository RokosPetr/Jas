using System;
using Microsoft.AspNetCore.Http;

namespace Jas.Web.Extensions;

/// <summary>
/// Přesměruje obrázky přes /images s doménovým prefixem, zachová i query a port.
/// - Absolutní URL "https://host[:port]/path?x=1" → "/images/host[:port]/path?x=1"
/// - Pokud input už začíná "/images/", vrátí se beze změny (i s query).
/// - Jinak očekává "host[/port]/path?x=1" → "/images/host[/port]/path?x=1"
/// </summary>
public static class ImageUrlExtensions
{
    public static string ToImageSrc(this string? input, HttpContext http)
    {
        if (string.IsNullOrWhiteSpace(input)) return string.Empty;
        var trimmed = input.Trim();

        // Absolutní URL → /images/{host[:port]}/{path}{?query}
        if (Uri.TryCreate(trimmed, UriKind.Absolute, out var abs) &&
            (abs.Scheme == Uri.UriSchemeHttp || abs.Scheme == Uri.UriSchemeHttps))
        {
            var authority = abs.IsDefaultPort ? abs.Host : $"{abs.Host}:{abs.Port}";
            var path = abs.AbsolutePath.TrimStart('/');
            return $"/images/{authority}/{path}{abs.Query}";
        }

        // Už relativní za /images → vrať jak je
        if (trimmed.StartsWith("/images/", StringComparison.OrdinalIgnoreCase))
            return trimmed;

        // Relativní "host[/port]/path?query"
        var qIndex = trimmed.IndexOf('?', StringComparison.Ordinal);
        var pathPart = qIndex >= 0 ? trimmed.Substring(0, qIndex) : trimmed;
        var queryPart = qIndex >= 0 ? trimmed.Substring(qIndex) : string.Empty;

        pathPart = pathPart.TrimStart('/'); // aby bylo "host[/port]/path"
        return $"/images/{pathPart}{queryPart}";
    }

    public static string ToAbsoluteImageUrl(this string? input, HttpContext http)
    {
        var rel = input.ToImageSrc(http);
        if (string.IsNullOrWhiteSpace(rel)) return string.Empty;

        var req = http.Request;
        return $"{req.Scheme}://{req.Host}{rel}";
    }
}
