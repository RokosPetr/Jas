using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class PovSortAccessLog
{
    public long LogId { get; set; }

    public DateTime LogTimeUtc { get; set; }

    public string SqlLogin { get; set; } = null!;

    public string? HostName { get; set; }

    public string? AppName { get; set; }

    public string? WorkbookName { get; set; }

    public string? WorkbookPath { get; set; }
}
