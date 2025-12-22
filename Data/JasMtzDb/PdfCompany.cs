using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class PdfCompany
{
    public int Id { get; set; }

    public string CompanyKey { get; set; } = null!;

    public string CompanyCode { get; set; } = null!;

    public string? Name { get; set; }

    public string? Email { get; set; }

    public string? Phone { get; set; }

    public string? ImgLogo { get; set; }

    public string? CssStyle { get; set; }

    public string? Url { get; set; }

    public bool Disable { get; set; }

    public virtual ICollection<PdfCompanyCatalog> PdfCompanyCatalogs { get; set; } = new List<PdfCompanyCatalog>();
}
