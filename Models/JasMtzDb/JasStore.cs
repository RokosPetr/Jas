using System;
using System.Collections.Generic;

namespace Jas.Models.JasMtzDb;

public partial class JasStore
{
    public int Id { get; set; }

    public string Name { get; set; } = null!;

    public virtual ICollection<JasDepartment> JasDepartments { get; set; } = new List<JasDepartment>();

    public virtual ICollection<User> Users { get; set; } = new List<User>();
}
