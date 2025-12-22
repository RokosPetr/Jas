using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class ViPdfFile
{
    public int IdFile { get; set; }

    public string Path { get; set; } = null!;

    public int? PageIndexFrom { get; set; }

    public int? PageIndexTo { get; set; }

    public int ProductStartsAt { get; set; }

    public int ProductEndsAt { get; set; }
}
