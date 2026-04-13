using System.Collections.Generic;
using System.Linq;
using System.Threading;
using System.Threading.Tasks;
using Jas.Data.JasIdentityDb;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using X.PagedList;
using X.PagedList.EF;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "PTG - admin")] // správa práv jen pro JAS adminy
    public class ManagePtgUsersModel : PageModel
    {
        private readonly JasIdentityDbContext _identityContext;

        public ManagePtgUsersModel(JasIdentityDbContext identityContext)
        {
            _identityContext = identityContext;
        }

        [BindProperty(SupportsGet = true)]
        public string? Search { get; set; }

        [BindProperty(SupportsGet = true)]
        public int PageNumber { get; set; } = 1;

        public int PageSize { get; } = 20;

        // Kolekce pro binding z formuláře (konkrétní typ, aby ji mohl model binder vytvořit)
        [BindProperty]
        public List<UserRow> Users { get; set; } = new();

        // Stránkovaná kolekce pro zobrazování a pager
        public IPagedList<UserRow> PagedUsers { get; private set; } = new PagedList<UserRow>(Enumerable.Empty<UserRow>(), 1, 1);

        public class UserRow
        {
            public string Id { get; set; } = string.Empty;
            public string UserName { get; set; } = string.Empty;
            public string Email { get; set; } = string.Empty;
            public bool IsPtgVoUser { get; set; }
        }

        public async Task OnGetAsync(CancellationToken ct)
        {
            await LoadAsync(ct);
        }

        public async Task<IActionResult> OnPostAsync(CancellationToken ct)
        {
            if (!ModelState.IsValid)
            {
                await LoadAsync(ct);
                return Page();
            }

            var role = await _identityContext.AspNetRoles
                .FirstOrDefaultAsync(r => r.Name == "PTG - vo", ct);

            if (role == null)
            {
                ModelState.AddModelError(string.Empty, "Role 'PTG - vo' neexistuje.");
                await LoadAsync(ct);
                return Page();
            }

            // Pro každý řádek porovnáme stav checkboxu s aktuálním stavem v DB a role přidáme/odebereme
            foreach (var row in Users)
            {
                var user = await _identityContext.AspNetUsers
                    .Include(u => u.Roles)
                    .FirstOrDefaultAsync(u => u.Id == row.Id, ct);

                if (user == null)
                {
                    continue;
                }

                // při udělení přístupu zajistit potvrzený e-mail a odemčený účet
                if (row.IsPtgVoUser)
                {
                    user.EmailConfirmed = true;
                    user.LockoutEnabled = true;
                    user.LockoutEnd = null;
                    user.AccessFailedCount = 0;
                }

                bool hasRole = user.Roles.Any(r => r.Id == role.Id);

                if (row.IsPtgVoUser && !hasRole)
                {
                    user.Roles.Add(role);
                }
                else if (!row.IsPtgVoUser && hasRole)
                {
                    var existing = user.Roles.First(r => r.Id == role.Id);
                    user.Roles.Remove(existing);
                }
            }

            await _identityContext.SaveChangesAsync(ct);

            TempData["StatusMessage"] = "Změny byly uloženy.";
            return RedirectToPage(new { Search, PageNumber });
        }

        private async Task LoadAsync(CancellationToken ct)
        {
            var query = _identityContext.AspNetUsers
                .Include(u => u.Roles)
                .AsQueryable();

            if (!string.IsNullOrWhiteSpace(Search))
            {
                var pattern = $"%{Search}%";
                query = query.Where(u => EF.Functions.Like(u.UserName, pattern) || EF.Functions.Like(u.Email, pattern));
            }

            var projected = query
                .OrderBy(u => u.UserName)
                .Select(u => new UserRow
                {
                    Id = u.Id,
                    UserName = u.UserName,
                    Email = u.Email,
                    IsPtgVoUser = u.Roles.Any(r => r.Name == "PTG - vo")
                });

            PagedUsers = await projected.ToPagedListAsync(PageNumber <= 0 ? 1 : PageNumber, PageSize);

            // Pro aktuální stránku naplníme bindovatelný seznam
            Users = PagedUsers.ToList();
        }
    }
}
