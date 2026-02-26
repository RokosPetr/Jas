using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using AutoMapper;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
using Jas.Models.Srv;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using StatusEnum = Jas.Globals.Srv.Enums.Status;

namespace Jas.Areas.Srv.Pages
{
    [Area("Srv")]
    [Authorize(Roles = "SRV - admin,SRV - user")]
    public class IndexModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly UserManager<JasUser> _userManager;

        public IList<SrvMaintenanceRequestModel> Requests { get; private set; } = [];

        public IndexModel(
            JasMtzDbContext context,
            IMapper mapper,
            UserManager<JasUser> userManager)
        {
            _context = context;
            _mapper = mapper;
            _userManager = userManager;
        }

        public async Task<IActionResult> OnGetAsync(string? filter)
        {
            // základní dotaz pro požadavky
            var query = _context.SrvMaintenanceRequests
                .AsNoTracking()
                .AsQueryable();

            // pokud je uživatel jen SRV - user (ne admin), zobrazit jen jeho vlastní požadavky
            bool isAdmin = User.IsInRole("SRV - admin");
            if (!isAdmin)
            {
                var currentUserId = _userManager.GetUserId(User);
                if (!string.IsNullOrEmpty(currentUserId))
                {
                    query = query.Where(r => r.IdUser == currentUserId);
                }
                else
                {
                    // bez identifikace uživatele nevracej nic
                    query = query.Where(r => false);
                }
            }

            // načteme (používá se pro jednotlivé seznamy ve view)
            var entities = await query
                .OrderByDescending(r => r.CreatedDate)
                .ToListAsync();

            Requests = _mapper.Map<List<SrvMaintenanceRequestModel>>(entities);

            // žádná data – jen zobraz stránku bez redirectu
            if (Requests.Count == 0)
            {
                return Page();
            }

            // pokud filtr chybí, přesměruj na první stav, který má záznam
            if (string.IsNullOrWhiteSpace(filter))
            {
                string? targetFilter = null;

                if (Requests.Any(r => r.Status == (int)StatusEnum.New))
                {
                    targetFilter = "new-requests";
                }
                else if (Requests.Any(r => r.Status == (int)StatusEnum.InProgress))
                {
                    targetFilter = "inprogress-requests";
                }
                else if (Requests.Any(r => r.Status == (int)StatusEnum.Resolved))
                {
                    targetFilter = "resolved-requests";
                }
                else if (Requests.Any(r => r.Status == (int)StatusEnum.Cancelled))
                {
                    targetFilter = "cancelled-requests";
                }

                if (!string.IsNullOrEmpty(targetFilter))
                {
                    // canonical URL – /Srv/Index/{filter}
                    return RedirectToPage("/Index", new { filter = targetFilter });
                }
            }

            // doplnit názvy oddělení (středisko) a jména uživatelů
            await LoadDepartmentsAsync();
            await LoadUsersAsync();

            // filtr je nastavený (nebo nebyl nalezen žádný jiný vhodný) – vykreslí se podle Razor logiky
            return Page();
        }

        private async Task LoadDepartmentsAsync()
        {
            if (Requests is null || Requests.Count == 0)
            {
                return;
            }

            var departmentIds = Requests
                .Select(r => r.IdDepartment)
                .Where(id => id != 0)
                .Distinct()
                .ToList();

            if (departmentIds.Count == 0)
            {
                return;
            }

            var departments = await _context.JasDepartments
                .AsNoTracking()
                .Where(d => departmentIds.Contains(d.Id))
                .ToDictionaryAsync(d => d.Id, d => d.Name);

            foreach (var request in Requests)
            {
                if (request.IdDepartment != 0 &&
                    departments.TryGetValue((int)request.IdDepartment!, out var name))
                {
                    request.Department = name!;
                }
            }
        }

        private async Task LoadUsersAsync()
        {
            if (Requests is null || Requests.Count == 0)
            {
                return;
            }

            var userIds = Requests
                .Select(r => r.IdUser)
                .Where(id => !string.IsNullOrEmpty(id))
                .Distinct()
                .ToList();

            if (userIds.Count == 0)
            {
                return;
            }

            // přes JasIdentity (UserManager) načteme jména
            var users = await _userManager.Users
                .Where(u => userIds.Contains(u.Id))
                .Select(u => new { u.Id, u.Name, u.UserName })
                .ToListAsync();

            var userDict = users.ToDictionary(
                u => u.Id,
                u => string.IsNullOrWhiteSpace(u.Name) ? u.UserName : u.Name);

            foreach (var request in Requests)
            {
                if (!string.IsNullOrEmpty(request.IdUser) &&
                    userDict.TryGetValue(request.IdUser, out var name))
                {
                    request.UserName = name ?? string.Empty;
                }
            }
        }
    }
}