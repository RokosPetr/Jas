using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class ViPtgItemLastPriceTagChange
{
    public string RegNumber { get; set; } = null!;

    public DateTime? LastChangeDate { get; set; }

    public DateTime? NameLastChangeDate { get; set; }

    public DateTime? DescriptionLastChangeDate { get; set; }

    public DateTime? SurfaceLastChangeDate { get; set; }

    public DateTime? PriceNnLastChangeDate { get; set; }

    public DateTime? SizeLastChangeDate { get; set; }

    public DateTime? DiscardedLastChangeDate { get; set; }

    public DateTime? PriceLastChangeDate { get; set; }

    public DateTime? InsertLastChangeDate { get; set; }

    public DateTime? QrLastChangeDate { get; set; }

    public DateTime? UnitLastChangeDate { get; set; }

    public DateTime? RectificationLastChangeDate { get; set; }

    public DateTime? FrostLastChangeDate { get; set; }

    public DateTime? OrigNameLastChangeDate { get; set; }

    public DateTime? DiscountLastChangeDate { get; set; }

    public DateTime? AbrasionLastChangeDate { get; set; }

    public DateTime? PriceJasLastChangeDate { get; set; }

    public DateTime? OutletLastChangeDate { get; set; }

    public DateTime? OutletQrLastChangeDate { get; set; }

    public DateTime? AntislipLastChangeDate { get; set; }
}
