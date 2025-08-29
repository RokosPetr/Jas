using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class JasDepartment
{
    public int Id { get; set; }

    public int? IdStore { get; set; }

    public string? Name { get; set; }

    public string? Address { get; set; }

    public virtual JasStore? IdStoreNavigation { get; set; }

    public virtual ICollection<MtzOrder> MtzOrders { get; set; } = new List<MtzOrder>();
}
