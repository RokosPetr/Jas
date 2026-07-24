using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class PovSortVoVersion
{
    public int VersionId { get; set; }

    public string VersionStatus { get; set; } = null!;

    public DateTime CreatedAt { get; set; }

    public DateTime? FinalizedAt { get; set; }

    public string CreatedBy { get; set; } = null!;

    public string? Note { get; set; }

    public DateTime LastModifiedAt { get; set; }

    public string? FinalizedBy { get; set; }

    public virtual ICollection<PovSortVoItem> PovSortVoItems { get; set; } = new List<PovSortVoItem>();

    public virtual ICollection<PovSortVoSummaryCache> PovSortVoSummaryCaches { get; set; } = new List<PovSortVoSummaryCache>();
}
