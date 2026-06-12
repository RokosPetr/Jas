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
using X.PagedList;
using X.PagedList.Extensions;
using StatusEnum = Jas.Globals.Srv.Enums.Status;
using RepairCategory = Jas.Globals.Srv.Enums.RepairCategory; // Added just in case it's in the same namespace as Status

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

        [BindProperty(SupportsGet = true)]
        public int PageNumber { get; set; } = 1;

        public int PageSize { get; } = 20;

        [BindProperty(SupportsGet = true)]
        public int? DepartmentFilterId { get; set; }

        [BindProperty(SupportsGet = true)]
        public DateTime? CreatedFrom { get; set; }

        [BindProperty(SupportsGet = true)]
        public DateTime? CreatedTo { get; set; }

        public class DepartmentOption
        {
            public int Id { get; set; }
            public string Name { get; set; } = string.Empty;
        }

        public List<DepartmentOption> Departments { get; private set; } = new();

        public IPagedList<SrvMaintenanceRequestModel> PagedRequests { get; private set; } = null!;

        public IndexModel(
            JasMtzDbContext context,
            IMapper mapper,
            UserManager<JasUser> userManager)
        {
            _context = context;
            _mapper = mapper;
            _userManager = userManager;
        }

        public async Task<IActionResult> OnGetAsync(string? filter, int? pageNumber)
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

            // filtr podle oddělení (jen pro admina má smysl vybírat ze všech středisek)
            if (DepartmentFilterId.HasValue && DepartmentFilterId.Value != 0)
            {
                query = query.Where(r => r.IdDepartment == DepartmentFilterId.Value);
            }

            // filtr podle data vytvoření
            if (CreatedFrom.HasValue)
            {
                query = query.Where(r => r.CreatedDate >= CreatedFrom.Value);
            }

            if (CreatedTo.HasValue)
            {
                // koncový čas je do konce dne (23:59:59.999), aby se zahrnuly všechny záznamy i s časem
                var endOfDay = CreatedTo.Value.Date.AddDays(1).AddTicks(-1);
                query = query.Where(r => r.CreatedDate <= endOfDay);
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
                else if (Requests.Any(r => r.Status == (int)StatusEnum.ToConfirm))
                {
                    targetFilter = "toconfirm-requests";
                }
                else if (Requests.Any(r => r.Status == (int)StatusEnum.Resolved))
                {
                    targetFilter = "resolved-requests";
                }
                else if (Requests.Any(r => r.Status == (int)StatusEnum.Returned))
                {
                    targetFilter = "returned-requests";
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

            // stránkování podle filtru stavu
            var stateFilter = filter switch
            {
                "new-requests"        => (int)StatusEnum.New,
                "inprogress-requests" => (int)StatusEnum.InProgress,
                "toconfirm-requests"  => (int)StatusEnum.ToConfirm,
                "resolved-requests"   => (int)StatusEnum.Resolved,
                "returned-requests"   => (int)StatusEnum.Returned,
                "cancelled-requests"  => (int)StatusEnum.Cancelled,
                _                      => (int)StatusEnum.New
            };

            var filtered = Requests
                .Where(r => r.Status == stateFilter)
                .OrderByDescending(r => r.CreatedDate)
                .ToList();

            PageNumber = pageNumber.GetValueOrDefault(PageNumber);
            if (PageNumber <= 0)
            {
                PageNumber = 1;
            }

            PagedRequests = filtered.ToPagedList(PageNumber, PageSize);

            // pro zobrazení pracujeme jen s aktuální stránkou
            Requests = PagedRequests.ToList();

            // doplnit názvy oddělení (středisko) a jména uživatelů
            await LoadDepartmentsAsync();
            await LoadUsersAsync();
            await LoadDepartmentFilterAsync();

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

        private async Task LoadDepartmentFilterAsync()
        {
            var departments = await _context.JasDepartments
                .AsNoTracking()
                .OrderBy(d => d.Name)
                .Select(d => new { d.Id, d.Name })
                .ToListAsync();

            Departments = departments
                .Select(d => new DepartmentOption
                {
                    Id = d.Id,
                    Name = d.Name ?? string.Empty
                })
                .ToList();
        }

        public string GetFormattedCategoryText(int userCategory, int? adminCategory)
        {
            string GetCategoryText(int cat) => cat switch
            {
                (int)RepairCategory.Light   => "Lehká (60 dní)",
                (int)RepairCategory.Serious => "Vážná (30 dní)",
                (int)RepairCategory.Urgent  => "Urgentní (5 dní)",
                _                           => "Neznámá"
            };

            string userCatText = GetCategoryText(userCategory);
            bool hasDifferentAdminCat = adminCategory.HasValue && adminCategory.Value != userCategory;
            
            if (hasDifferentAdminCat)
            {
                string adminCatText = GetCategoryText(adminCategory.Value);
                return $"{adminCatText} ({userCatText})"; 
            }

            return userCatText; 
        }

        public string GetFormattedRepairDateText(DateTime createdDate, int userCategory, int? adminCategory)
        {
            if (createdDate == default || userCategory == 0)
            {
                return string.Empty;
            }

            int GetDays(int cat) => cat switch
            {
                (int)RepairCategory.Light   => 60,
                (int)RepairCategory.Serious => 30,
                (int)RepairCategory.Urgent  => 5,
                _                           => 0
            };

            int userDays = GetDays(userCategory);
            string userDateText = userDays > 0 
                ? createdDate.AddDays(userDays).ToString("dd.MM.yyyy") 
                : string.Empty;

            bool hasDifferentAdminCat = adminCategory.HasValue && adminCategory.Value != userCategory;

            if (hasDifferentAdminCat)
            {
                int adminDays = GetDays(adminCategory.Value);
                string adminDateText = adminDays > 0 
                    ? createdDate.AddDays(adminDays).ToString("dd.MM.yyyy") 
                    : string.Empty;

                return $"{adminDateText} ({userDateText})";
            }

            return userDateText;
        }
    }
}