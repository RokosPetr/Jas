using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class PtgStandSearch
{
    public int Id { get; set; }

    public int? IdStand { get; set; }

    public string? Code { get; set; }

    public string? Name { get; set; }

    public string? StandProducerName { get; set; }

    public string? StandProducerAlias { get; set; }

    public string? K2producerName { get; set; }

    public string? K2seriesName { get; set; }

    public int? IdPlate { get; set; }

    public string? RegNumber { get; set; }

    public string? ItemName { get; set; }

    public string? Size { get; set; }
}
