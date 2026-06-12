using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class UserStoreAccess
{
    public string LoginName { get; set; } = null!;

    public int? StoreId { get; set; }
}
