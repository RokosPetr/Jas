using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class JasStore
{
    public int Id { get; set; }

    public string Name { get; set; } = null!;

    public virtual ICollection<JasDepartment> JasDepartments { get; set; } = new List<JasDepartment>();

    public virtual ICollection<MtzOrder> MtzOrders { get; set; } = new List<MtzOrder>();
}
