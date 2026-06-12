using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class PtgCustomer
{
    public string Login { get; set; } = null!;

    public string? FirstName { get; set; }

    public string? LastName { get; set; }

    public string? CompanyName { get; set; }

    public string? ShortName { get; set; }

    public string? City { get; set; }

    public decimal? Ico { get; set; }

    public string? Branch { get; set; }

    public string? Skp { get; set; }

    public string? Password { get; set; }

    public string? EmailCustomer { get; set; }

    public string? EmailSales { get; set; }

    public int? Objednavky { get; set; }

    public int? Sklad { get; set; }

    public int? Paleta { get; set; }

    public int? NedodanePolozky { get; set; }

    public int? SkladVlastniPobocky { get; set; }

    public int? SkladVsech { get; set; }

    public string? RabSkp { get; set; }

    public string? Street { get; set; }

    public string? Zip { get; set; }

    public string? Phone { get; set; }

    public int? K2CustomerId { get; set; }

    public int? K2PriceGroupId { get; set; }

    public int? Cipa { get; set; }

    public int? Voj { get; set; }
}
