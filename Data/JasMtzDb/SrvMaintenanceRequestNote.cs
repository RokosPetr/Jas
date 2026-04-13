using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class SrvMaintenanceRequestNote
{
    public int Id { get; set; }

    public int IdRequest { get; set; }

    public byte NoteType { get; set; }

    public string? NoteText { get; set; }

    public DateTime CreatedAt { get; set; }

    public string? IdUser { get; set; }

    public virtual SrvMaintenanceRequest IdRequestNavigation { get; set; } = null!;
}
