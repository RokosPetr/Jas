using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class ViPdfPtPriceTagHistory
{
    public int IdStand { get; set; }

    public int IdMkStand { get; set; }

    public string Name { get; set; } = null!;

    public int Type { get; set; }

    public bool PiecePriceTag { get; set; }

    public bool PlatePriceTag { get; set; }

    public string Code { get; set; } = null!;

    public DateTime? SentDate { get; set; }

    public bool? B2b { get; set; }

    public bool? B2c { get; set; }

    public DateTime? ChangeDate { get; set; }
}
