using Microsoft.AspNetCore.Identity;
using System.ComponentModel.DataAnnotations;

namespace Jas.Data.JasIdentityApp
{
    public class JasUser : IdentityUser
    {
        [MaxLength(256)]
        public string? Name { get; set; }

        [MaxLength(256)]
        public string? InternalLogin { get; set; }

        public int? StoreId { get; set; }
        public int? DepartmentId { get; set; }
        public string? Ico { get; set; }
        public int? Mop9Voj { get; set; }

    }
}
