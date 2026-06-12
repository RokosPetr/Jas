using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class RabatPravidla
{
    public int IdPravidla { get; set; }

    public int IdVerze { get; set; }

    public int Sku { get; set; }

    public int Psku { get; set; }

    public string ProskuK2 { get; set; } = null!;

    public virtual RabatVerze IdVerzeNavigation { get; set; } = null!;

    public virtual ICollection<RabatHodnoty> RabatHodnoties { get; set; } = new List<RabatHodnoty>();
}
