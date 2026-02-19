using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class SrvMaintenanceRequest
{
    public int Id { get; set; }

    public string IdSolver { get; set; } = null!;

    public string IdUser { get; set; } = null!;

    public int IdDepartment { get; set; }

    public int IdStore { get; set; }

    public DateOnly CreatedDate { get; set; }

    public DateOnly? RemovedDate { get; set; }

    public DateOnly? DueDate { get; set; }

    public DateOnly? PlannedRepairDate { get; set; }

    public string CostCenter { get; set; } = null!;

    public string IssueDescription { get; set; } = null!;

    public string? RepairDescription { get; set; }

    public int Status { get; set; }

    public int RepairCategory { get; set; }

    public int? RemainingTimeDays { get; set; }

    public int? RequiredResolutionDays { get; set; }

    public decimal? EstimatedCost { get; set; }

    public decimal? ActualCost { get; set; }
}
