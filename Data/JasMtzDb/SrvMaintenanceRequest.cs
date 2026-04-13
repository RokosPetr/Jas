using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class SrvMaintenanceRequest
{
    public int Id { get; set; }

    public string IdSolver { get; set; } = null!;

    public string IdUser { get; set; } = null!;

    public int? IdDepartment { get; set; }

    public int? IdStore { get; set; }

    public DateTime CreatedDate { get; set; }

    public DateTime? RemovedDate { get; set; }

    public DateTime? DueDate { get; set; }

    public DateTime? PlannedRepairDate { get; set; }

    public string IssueDescription { get; set; } = null!;

    public string? RepairDescription { get; set; }

    public string? ReturnDescription { get; set; }

    public int Status { get; set; }

    public int RepairCategory { get; set; }

    public int? RepairCategoryAdmin { get; set; }

    public decimal? EstimatedCost { get; set; }

    public decimal? ActualCost { get; set; }

    public virtual ICollection<SrvMaintenanceRequestNote> SrvMaintenanceRequestNotes { get; set; } = new List<SrvMaintenanceRequestNote>();
}
