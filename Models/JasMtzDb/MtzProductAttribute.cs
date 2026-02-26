using System;
using System.Collections.Generic;

namespace Jas.Models.JasMtzDb;

public partial class MtzProductAttribute
{
    public int IdProduct { get; set; }

    public int IdProductAttribute { get; set; }

    public string Value { get; set; } = null!;

    public string? ProductCode { get; set; }

    public virtual MtzAttribute IdProductAttributeNavigation { get; set; } = null!;

    public virtual MtzProduct IdProductNavigation { get; set; } = null!;
}
