using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfPtType
{
    public int Id { get; set; }

    public string Name { get; set; } = null!;

    public virtual ICollection<PdfPtFile> PdfPtFiles { get; set; } = new List<PdfPtFile>();
}
