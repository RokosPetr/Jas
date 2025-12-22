using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class PdfFile
{
    public int Id { get; set; }

    public string Path { get; set; } = null!;

    public int NumberOfPages { get; set; }

    public string? CompanyCode { get; set; }

    public string? PartName { get; set; }

    public long? Size { get; set; }

    public DateTime? Modified { get; set; }

    public bool Disable { get; set; }

    public virtual ICollection<PdfCompanyCatalog> PdfCompanyCatalogs { get; set; } = new List<PdfCompanyCatalog>();

    public virtual ICollection<PdfQr> PdfQrs { get; set; } = new List<PdfQr>();

    public virtual ICollection<PdfRegNumber> PdfRegNumbers { get; set; } = new List<PdfRegNumber>();
}
