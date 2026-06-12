using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfPtStandUserHistory
{
    public int Id { get; set; }

    public int? IdPtStand { get; set; }

    public string IdUser { get; set; } = null!;

    public DateTime? EmailDate { get; set; }

    public DateTime? CheckDate { get; set; }
}
