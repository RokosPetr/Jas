using System;
using System.Collections.Generic;

namespace Jas.Models.JasMtzDb;

public partial class MtzAttribute
{
    public int Id { get; set; }

    public string Code { get; set; } = null!;

    public string Name { get; set; } = null!;
}
