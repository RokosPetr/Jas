using System.IO;
using System.Threading;
using System.Threading.Tasks;

namespace Jas.Application.Abstractions;

public interface IImageStore
{
    /// <summary>Zajistí, že obrázek je lokálně dostupný. Vrací true, když se ho podařilo zajistit (existuje nebo se stáhne).</summary>
    Task<bool> TryEnsureLocalAsync(string relativePath, CancellationToken ct = default);

    /// <summary>Vrátí true, pokud obrázek lokálně existuje.</summary>
    bool Exists(string relativePath);

    /// <summary>Otevře stream pro čtení (nebo null, pokud soubor není).</summary>
    Task<Stream?> OpenReadAsync(string relativePath, CancellationToken ct = default);

    /// <summary>Vrátí fyzickou cestu na disku (bez otevření souboru).</summary>
    string MapPath(string relativePath);
    public string ProductPath(string regNumber);
}
