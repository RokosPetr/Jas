using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfPtFile
{
    public int Id { get; set; }

    public int IdStand { get; set; }

    public int? IdCompany { get; set; }

    public int? IdType { get; set; }

    public string FilePath { get; set; } = null!;

    public DateTime? CreatedDate { get; set; }

    public DateTime? UpdatedDate { get; set; }

    public virtual PdfCompany? IdCompanyNavigation { get; set; }

    public virtual PdfPtStand IdStandNavigation { get; set; } = null!;

    public virtual PdfPtType? IdTypeNavigation { get; set; }

    public virtual ICollection<PdfPtEmailAttachment> PdfPtEmailAttachments { get; set; } = new List<PdfPtEmailAttachment>();
}
