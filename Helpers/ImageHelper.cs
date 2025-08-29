namespace Jas.Helpers
{
    public static class ImageHelper
    {
        public static string? NormalizeImageUrl(string? url)
        {
            if (string.IsNullOrWhiteSpace(url))
                return url;

            // Pokud už je to /images/... necháme být
            if (url.TrimStart().StartsWith("/images/", StringComparison.OrdinalIgnoreCase))
                return url;

            try
            {
                var uri = new Uri(url, UriKind.Absolute);
                if (uri.Scheme != Uri.UriSchemeHttp && uri.Scheme != Uri.UriSchemeHttps)
                    return url; // jen http/https převádíme

                // Zachovej "www" i port (pokud je)
                var authority = uri.IsDefaultPort ? uri.Host : $"{uri.Host}:{uri.Port}";

                // Slož relativní cestu k našemu endpointu a zachovej i query
                return $"/images/{authority}{uri.AbsolutePath}{uri.Query}";
            }
            catch (UriFormatException)
            {
                // URL není absolutní – může to být "host/cesta" nebo už relativní
                // Přesměruj to na /images stejně (podporuje i "host/cesta?x=1")
                var trimmed = url.TrimStart('/');
                return $"/images/{trimmed}";
            }
        }
    }
}
