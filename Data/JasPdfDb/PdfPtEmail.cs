using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfPtEmail
{
    public int Id { get; set; }

    public string? FromEmail { get; set; }

    public string? ToEmail { get; set; }

    public string? Bcc { get; set; }

    public string Subject { get; set; } = null!;

    public string Body { get; set; } = null!;

    public bool IsBodyHtml { get; set; }

    public DateTime? CreatedDate { get; set; }

    public DateTime? SentDate { get; set; }

    public virtual ICollection<PdfPtEmailAttachment> PdfPtEmailAttachments { get; set; } = new List<PdfPtEmailAttachment>();
}
