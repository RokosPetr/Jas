using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class RabatHodnoty
{
    public int IdHodnoty { get; set; }

    public int IdPravidla { get; set; }

    public string KodRabatu { get; set; } = null!;

    public decimal Rabat { get; set; }

    public string? Poznamka { get; set; }

    public virtual RabatPravidla IdPravidlaNavigation { get; set; } = null!;

    public virtual RabatTypy KodRabatuNavigation { get; set; } = null!;
}
