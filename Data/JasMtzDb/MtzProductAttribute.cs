using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class MtzProductAttribute
{
    public int IdProduct { get; set; }

    public int IdAttribute { get; set; }

    public string Value { get; set; } = null!;

    public string? ProductCode { get; set; }

    public virtual MtzAttribute IdAttributeNavigation { get; set; } = null!;

    public virtual MtzProduct IdProductNavigation { get; set; } = null!;
}
