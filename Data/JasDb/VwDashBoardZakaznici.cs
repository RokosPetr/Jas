using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class VwDashBoardZakaznici
{
    public string Source { get; set; } = null!;

    public string? CompanyRegNumber { get; set; }

    public string? CustomerAbbr { get; set; }

    public string? CustomerName { get; set; }

    public string? PostalCode { get; set; }

    public string? Branch { get; set; }
}
