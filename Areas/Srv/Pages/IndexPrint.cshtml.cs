using System;
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
using RepairCategory = Jas.Globals.Srv.Enums.RepairCategory;

namespace Jas.Areas.Srv.Pages
{
    [Area("Srv")]
    [Authorize(Roles = "SRV - admin,SRV - user")]
    public class IndexPrintModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly UserManager<JasUser> _userManager;

        public IList<SrvMaintenanceRequestModel> Requests { get; private set; } = [];

        [BindProperty(SupportsGet = true)]
        public int? DepartmentFilterId { get; set; }

        [BindProperty(SupportsGet = true)]
        public DateTime? CreatedFrom { get; set; }

        [BindProperty(SupportsGet = true)]
        public DateTime? CreatedTo { get; set; }

        public IndexPrintModel(
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
            var query = _context.SrvMaintenanceRequests
                .AsNoTracking()
                .AsQueryable();

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
                    query = query.Where(r => false);
                }
            }

            if (DepartmentFilterId.HasValue && DepartmentFilterId.Value != 0)
            {
                query = query.Where(r => r.IdDepartment == DepartmentFilterId.Value);
            }

            if (CreatedFrom.HasValue)
            {
                query = query.Where(r => r.CreatedDate >= CreatedFrom.Value);
            }

            if (CreatedTo.HasValue)
            {
                var endOfDay = CreatedTo.Value.Date.AddDays(1).AddTicks(-1);
                query = query.Where(r => r.CreatedDate <= endOfDay);
            }

            var entities = await query
                .OrderByDescending(r => r.CreatedDate)
                .ToListAsync();

            Requests = _mapper.Map<List<SrvMaintenanceRequestModel>>(entities);

            if (Requests.Count == 0)
            {
                return Page();
            }

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

            Requests = Requests
                .Where(r => r.Status == stateFilter)
                .OrderByDescending(r => r.CreatedDate)
                .ToList();

            await LoadDepartmentsAsync();
            await LoadUsersAsync();

            return Page();
        }

        private async Task LoadDepartmentsAsync()
        {
            if (Requests == null || Requests.Count == 0) return;

            var departmentIds = Requests.Select(r => r.IdDepartment).Where(id => id != 0).Distinct().ToList();
            if (departmentIds.Count == 0) return;

            var departments = await _context.JasDepartments
                .AsNoTracking()
                .Where(d => departmentIds.Contains(d.Id))
                .ToDictionaryAsync(d => d.Id, d => d.Name);

            foreach (var request in Requests)
            {
                if (request.IdDepartment != 0 && departments.TryGetValue((int)request.IdDepartment!, out var name))
                {
                    request.Department = name!;
                }
            }
        }

        private async Task LoadUsersAsync()
        {
            if (Requests == null || Requests.Count == 0) return;

            var userIds = Requests.Select(r => r.IdUser).Where(id => !string.IsNullOrEmpty(id)).Distinct().ToList();
            if (userIds.Count == 0) return;

            var users = await _userManager.Users
                .Where(u => userIds.Contains(u.Id))
                .Select(u => new { u.Id, u.Name, u.UserName })
                .ToListAsync();

            var userDict = users.ToDictionary(
                u => u.Id,
                u => string.IsNullOrWhiteSpace(u.Name) ? u.UserName : u.Name);

            foreach (var request in Requests)
            {
                if (!string.IsNullOrEmpty(request.IdUser) && userDict.TryGetValue(request.IdUser, out var name))
                {
                    request.UserName = name ?? string.Empty;
                }
            }
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
            if (createdDate == default || userCategory == 0) return string.Empty;

            int GetDays(int cat) => cat switch
            {
                (int)RepairCategory.Light   => 60,
                (int)RepairCategory.Serious => 30,
                (int)RepairCategory.Urgent  => 5,
                _                           => 0
            };

            int userDays = GetDays(userCategory);
            string userDateText = userDays > 0 ? createdDate.AddDays(userDays).ToString("dd.MM.yyyy") : string.Empty;

            bool hasDifferentAdminCat = adminCategory.HasValue && adminCategory.Value != userCategory;

            if (hasDifferentAdminCat)
            {
                int adminDays = GetDays(adminCategory.Value);
                string adminDateText = adminDays > 0 ? createdDate.AddDays(adminDays).ToString("dd.MM.yyyy") : string.Empty;
                return $"{adminDateText} ({userDateText})";
            }

            return userDateText;
        }
    }
}
