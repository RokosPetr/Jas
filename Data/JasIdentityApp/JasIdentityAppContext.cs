using Microsoft.AspNetCore.Identity.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore;

namespace Jas.Data.JasIdentityApp
{
    public class JasIdentityAppContext : IdentityDbContext<JasUser>
    {
        public JasIdentityAppContext(DbContextOptions<JasIdentityAppContext> options)
            : base(options)
        {
        }

    }
}
