using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class RabatVerze
{
    public int IdVerze { get; set; }

    public DateTime DatumOd { get; set; }

    public string? Autor { get; set; }

    public string? Poznamka { get; set; }

    public DateTime Vytvoreno { get; set; }

    public string StavVerze { get; set; } = null!;

    public virtual ICollection<RabatPravidla> RabatPravidlas { get; set; } = new List<RabatPravidla>();
}
