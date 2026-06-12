using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class VwSalesSummarySalesSourceMop
{
    public short Pobocka { get; set; }

    public short Rok { get; set; }

    public int CisloPohybu { get; set; }

    public int CisloDokladu { get; set; }

    public string Reg { get; set; } = null!;

    public string Dopl { get; set; } = null!;

    public short Sku { get; set; }

    public short Psku { get; set; }

    public DateOnly Datum { get; set; }

    public int Znamenko { get; set; }

    public decimal? MnozstviSeZnamenkem { get; set; }

    public decimal? CastkaSdphseZnamenkem { get; set; }
}
