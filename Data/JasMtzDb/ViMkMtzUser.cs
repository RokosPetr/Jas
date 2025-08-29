using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class ViMkMtzUser
{
    public string Name { get; set; } = null!;

    public string Email { get; set; } = null!;

    public string Username { get; set; } = null!;

    public string? InternalLogin { get; set; }

    public decimal? Store { get; set; }
}
