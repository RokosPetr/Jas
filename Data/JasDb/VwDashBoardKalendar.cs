using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class VwDashBoardKalendar
{
    public DateTime? Date { get; set; }

    public int? Year { get; set; }

    public int? Month { get; set; }

    public int? Day { get; set; }

    public int? Quarter { get; set; }

    public int? Week { get; set; }

    public string? MonthName { get; set; }

    public string? MonthNameShort { get; set; }

    public string? DayName { get; set; }

    public int IsWorkday { get; set; }
}
