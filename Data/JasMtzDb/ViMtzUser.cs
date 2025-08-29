using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class ViMtzUser
{
    public string? UserName { get; set; }

    public string? Email { get; set; }

    public string? NormalizedEmail { get; set; }

    public string? InternalLogin { get; set; }

    public string? Name { get; set; }

    public int? StoreId { get; set; }
}
