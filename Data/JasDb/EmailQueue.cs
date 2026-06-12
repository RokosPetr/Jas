using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class EmailQueue
{
    public long Id { get; set; }

    public string ToEmail { get; set; } = null!;

    public string? CcEmail { get; set; }

    public string? BccEmail { get; set; }

    public string Subject { get; set; } = null!;

    public string Body { get; set; } = null!;

    public bool IsBodyHtml { get; set; }

    public string Status { get; set; } = null!;

    public int RetryCount { get; set; }

    public int MaxRetryCount { get; set; }

    public DateTime CreatedAt { get; set; }

    public DateTime ScheduledAt { get; set; }

    public DateTime? ProcessingAt { get; set; }

    public DateTime? SentAt { get; set; }

    public string? LastError { get; set; }

    public string? LockedBy { get; set; }

    public int? EmailType { get; set; }

    public int? K2companyId { get; set; }

    public virtual ICollection<EmailQueueStand> EmailQueueStands { get; set; } = new List<EmailQueueStand>();
}
