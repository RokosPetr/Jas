using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class PdfCatalog
{
    public int Id { get; set; }

    public string CatalogKey { get; set; } = null!;

    public string Title { get; set; } = null!;

    public bool Landscape { get; set; }

    public bool Disable { get; set; }

    public virtual ICollection<PdfCompanyCatalog> PdfCompanyCatalogs { get; set; } = new List<PdfCompanyCatalog>();
}
