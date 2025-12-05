using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class JasProducer
{
    public int Id { get; set; }

    public int? K2Id { get; set; }

    public string? K2Code { get; set; }

    public int? MopSku { get; set; }

    public int? MkId { get; set; }

    public string? Name { get; set; }

    public string? Alias { get; set; }

    public string? LogoImage { get; set; }

    public int? FilterGroup { get; set; }
}
