using System;
using System.Collections.Generic;

namespace Jas.Models.JasMtzDb;

public partial class User
{
    public string Id { get; set; } = null!;

    public string? UserName { get; set; }

    public string? NormalizedUserName { get; set; }

    public string? Email { get; set; }

    public string? NormalizedEmail { get; set; }

    public bool EmailConfirmed { get; set; }

    public string? PasswordHash { get; set; }

    public string? SecurityStamp { get; set; }

    public string? ConcurrencyStamp { get; set; }

    public string? PhoneNumber { get; set; }

    public bool PhoneNumberConfirmed { get; set; }

    public bool TwoFactorEnabled { get; set; }

    public DateTimeOffset? LockoutEnd { get; set; }

    public bool LockoutEnabled { get; set; }

    public int AccessFailedCount { get; set; }

    public string? InternalLogin { get; set; }

    public string? Name { get; set; }

    public int? StoreId { get; set; }

    public DateTime? CreatedAt { get; set; }

    public string? CreatedBy { get; set; }

    public DateTime? UpdatedAt { get; set; }

    public string? UpdatedBy { get; set; }

    public bool? Deleted { get; set; }

    public DateTime? DeleteddAt { get; set; }

    public string? DeletedBy { get; set; }

    public int? IdStore { get; set; }

    public int? IdDepartment { get; set; }

    public virtual JasDepartment? IdDepartmentNavigation { get; set; }

    public virtual JasStore? IdStoreNavigation { get; set; }

    public virtual ICollection<MtzOrder> MtzOrders { get; set; } = new List<MtzOrder>();

    public virtual ICollection<UserClaim> UserClaims { get; set; } = new List<UserClaim>();

    public virtual ICollection<UserLogin> UserLogins { get; set; } = new List<UserLogin>();

    public virtual ICollection<UserToken> UserTokens { get; set; } = new List<UserToken>();

    public virtual ICollection<Role> Roles { get; set; } = new List<Role>();
}
