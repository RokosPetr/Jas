using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class GnReward
{
    public string Uid { get; set; } = null!;

    public int Store { get; set; }

    public string InternalId { get; set; } = null!;

    public int GnNumber { get; set; }

    public DateOnly RecordDate { get; set; }

    public int Year { get; set; }

    public string MonthName { get; set; } = null!;

    public string PersonName { get; set; } = null!;

    public string RoleName { get; set; } = null!;

    public int OrderSequence { get; set; }

    public string OrderNumber { get; set; } = null!;

    public decimal OrderBaseAmount { get; set; }

    public decimal BonusBaseAmount { get; set; }

    public decimal Rate { get; set; }

    public decimal RewardAmount { get; set; }

    public string StoreName { get; set; } = null!;
}
