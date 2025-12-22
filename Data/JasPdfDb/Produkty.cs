using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class Produkty
{
    public int Id { get; set; }

    public int? ParentId { get; set; }

    public string? Title { get; set; }

    public int? PageFrom { get; set; }

    public int? PageTo { get; set; }

    public int? PageCount { get; set; }
}
