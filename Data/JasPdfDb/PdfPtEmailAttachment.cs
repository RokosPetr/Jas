using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfPtEmailAttachment
{
    public int Id { get; set; }

    public int IdEmail { get; set; }

    public int IdFile { get; set; }

    public virtual PdfPtEmail IdEmailNavigation { get; set; } = null!;

    public virtual PdfPtFile IdFileNavigation { get; set; } = null!;
}
