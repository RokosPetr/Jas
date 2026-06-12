using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class JasDepartment
{
    public int Id { get; set; }

    public int? IdStore { get; set; }

    public string? Name { get; set; }

    public string? Address { get; set; }
}
