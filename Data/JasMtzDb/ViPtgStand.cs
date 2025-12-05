using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class ViPtgStand
{
    public int IdStand { get; set; }

    public int IdMkStand { get; set; }

    public int? IdMkProducer { get; set; }

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

    public string StandProducerName { get; set; } = null!;

    public string? StandProducerAlias { get; set; }

    public string? K2producerName { get; set; }

    public string? K2seriesName { get; set; }

    public int IdPlate { get; set; }

    public int PlateOrder { get; set; }

    public string? RegNumber { get; set; }

    public string? ItemName { get; set; }

    public string? Size { get; set; }
}
