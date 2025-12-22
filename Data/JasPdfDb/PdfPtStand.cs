using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfPtStand
{
    public int Id { get; set; }

    public int IdMkStand { get; set; }

    public int? IdMkProducer { get; set; }

    public string Code { get; set; } = null!;

    public string Name { get; set; } = null!;

    public int Type { get; set; }

    public int? UnitCount { get; set; }

    public bool Disable { get; set; }

    public bool ChangeEmail { get; set; }

    public bool PiecePriceTag { get; set; }

    public bool PlatePriceTag { get; set; }

    public bool B2b { get; set; }

    public bool B2c { get; set; }

    public string? Qr { get; set; }

    public string? Picture { get; set; }

    public string? SecondPicture { get; set; }

    public virtual ICollection<PdfPtFile> PdfPtFiles { get; set; } = new List<PdfPtFile>();

    public virtual ICollection<PdfPtPlate> PdfPtPlates { get; set; } = new List<PdfPtPlate>();
}
