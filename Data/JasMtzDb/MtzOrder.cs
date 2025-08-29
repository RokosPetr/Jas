using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class MtzOrder
{
    public int Id { get; set; }

    public string IdUser { get; set; } = null!;

    public int? IdDepartment { get; set; }

    public string UserName { get; set; } = null!;

    public string UserEmail { get; set; } = null!;

    public DateTime OrderDate { get; set; }

    public DateTime? SentDate { get; set; }

    public int State { get; set; }

    public int? StoreId { get; set; }

    public string? Comment { get; set; }

    public virtual JasDepartment? IdDepartmentNavigation { get; set; }

    public virtual ICollection<MtzOrderItem> MtzOrderItems { get; set; } = new List<MtzOrderItem>();

    public virtual JasStore? Store { get; set; }
}
