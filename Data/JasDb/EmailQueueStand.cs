using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class EmailQueueStand
{
    public long Id { get; set; }

    public long IdEmailQueue { get; set; }

    public int IdStand { get; set; }

    public DateOnly ChangeDate { get; set; }

    public string? Ico { get; set; }

    public virtual EmailQueue IdEmailQueueNavigation { get; set; } = null!;
}
