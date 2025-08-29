using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class MtzAttribute
{
    public int Id { get; set; }

    public string Code { get; set; } = null!;

    public string Name { get; set; } = null!;

    public virtual ICollection<MtzProductAttribute> MtzProductAttributes { get; set; } = new List<MtzProductAttribute>();
}
