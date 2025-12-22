using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfPtPlate
{
    public int Id { get; set; }

    public int IdMkPlate { get; set; }

    public int IdPtStand { get; set; }

    public string? Description { get; set; }

    public string? Qr { get; set; }

    public int PlateOrder { get; set; }

    public bool Disable { get; set; }

    public string? Picture { get; set; }

    public virtual PdfPtStand IdPtStandNavigation { get; set; } = null!;

    public virtual ICollection<PdfPtPlateItem> PdfPtPlateItems { get; set; } = new List<PdfPtPlateItem>();
}
