using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class ViPdfPtChangeEmail
{
    public int IdStand { get; set; }

    public int IdMkStand { get; set; }

    public string Name { get; set; } = null!;

    public int Type { get; set; }

    public bool PiecePriceTag { get; set; }

    public bool PlatePriceTag { get; set; }

    public string Code { get; set; } = null!;

    public bool? B2b { get; set; }

    public bool? B2c { get; set; }

    public DateTime? SentDate { get; set; }

    public DateTime? ChangeDate { get; set; }

    public int? B2bPiFileId { get; set; }

    public DateTime? B2bPiDate { get; set; }

    public int? B2bPlFileId { get; set; }

    public DateTime? B2bPlDate { get; set; }

    public int? B2cPiFileId { get; set; }

    public DateTime? B2cPiDate { get; set; }

    public int? B2cPlFileId { get; set; }

    public DateTime? B2cPlDate { get; set; }
}
