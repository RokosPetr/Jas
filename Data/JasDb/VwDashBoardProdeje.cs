using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class VwDashBoardProdeje
{
    public string Source { get; set; } = null!;

    public DateOnly? SaleDate { get; set; }

    public int? SaleYear { get; set; }

    public int? SaleMonth { get; set; }

    public string? Reg { get; set; }

    public string? CompanyRegNumber { get; set; }

    public string? CustomerAbbr { get; set; }

    public string? CustomerName { get; set; }

    public string? Branch { get; set; }

    public decimal? Quantity { get; set; }

    public decimal? AmountNet { get; set; }

    public decimal? Margin { get; set; }
}
