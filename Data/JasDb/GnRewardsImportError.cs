using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class GnRewardsImportError
{
    public int Id { get; set; }

    public int Store { get; set; }

    public string FilePath { get; set; } = null!;

    public DateTime ErrorDate { get; set; }

    public string? UidRaw { get; set; }

    public string? InternalIdRaw { get; set; }

    public string? GnNumberRaw { get; set; }

    public string? RecordDateRaw { get; set; }

    public string? YearRaw { get; set; }

    public string? MonthNameRaw { get; set; }

    public string? PersonNameRaw { get; set; }

    public string? RoleNameRaw { get; set; }

    public string? OrderSequenceRaw { get; set; }

    public string? OrderNumberRaw { get; set; }

    public string? OrderBaseAmountRaw { get; set; }

    public string? BonusBaseAmountRaw { get; set; }

    public string? RateRaw { get; set; }

    public string? RewardAmountRaw { get; set; }

    public string? StoreNameRaw { get; set; }

    public string ErrorReason { get; set; } = null!;
}
