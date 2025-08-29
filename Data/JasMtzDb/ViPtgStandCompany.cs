using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class ViPtgStandCompany
{
    public int Id { get; set; }

    public int IdStand { get; set; }

    public int IdMkStand { get; set; }

    public string Ico { get; set; } = null!;

    public int Cipa { get; set; }

    public string Code { get; set; } = null!;

    public string Name { get; set; } = null!;

    public int Type { get; set; }

    public int? UnitCount { get; set; }

    public bool Disable { get; set; }

    public bool ChangeEmail { get; set; }

    public bool PiecePriceTag { get; set; }

    public bool PlatePriceTag { get; set; }

    public bool B2b { get; set; }

    public bool B2c { get; set; }

    public string? Qr { get; set; }

    public string? Picture { get; set; }

    public string? SecondPicture { get; set; }

    public int? IdMkProducer { get; set; }
}
