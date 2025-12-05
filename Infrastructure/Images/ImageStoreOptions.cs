using System;

namespace Jas.Infrastructure.Images;

public class ImageStoreOptions
{
    /// <summary>Podadresář ve wwwroot, kam se budou ukládat obrázky (např. "images").</summary>
    public string RootSubfolder { get; set; } = "images";

    /// <summary>TTL pro revalidaci (zjištění změny obsahu na remote). null/0 = revalidace vypnutá.</summary>
    public TimeSpan? RefreshTtl { get; set; } = TimeSpan.FromMinutes(30);

    /// <summary>Serve-Stale-While-Revalidate: pokud true, vracíme ihned lokál a revalidace běží na pozadí.</summary>
    public bool ServeStaleWhileRevalidate { get; set; } = true;

    /// <summary>Maximální počet paralelních revalidací.</summary>
    public int MaxConcurrentRevalidations { get; set; } = 8;

    /// <summary>Revalidace podle Content-Length na remote (HEAD / Range 0–0).</summary>
    public bool RevalidateByRemoteSize { get; set; } = true;

    /// <summary>Pokud https selže, povolit fallback na http.</summary>
    public bool AllowHttpFallback { get; set; } = true;
    public string ProductBasePath { get; set; } = "/images/www.koupelny-jas.cz/data/storage/images/_product";

}
