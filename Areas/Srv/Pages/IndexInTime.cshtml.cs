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
using X.PagedList;
using X.PagedList.Extensions;
using StatusEnum = Jas.Globals.Srv.Enums.Status;
using RepairCategory = Jas.Globals.Srv.Enums.RepairCategory;

namespace Jas.Areas.Srv.Pages
{
    [Area("Srv")]
    [Authorize(Roles = "SRV - admin,SRV - user")]
    public class IndexInTimeModel : PageModel
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

        public class DepartmentOption
        {
            public int Id { get; set; }
            public string Name { get; set; } = string.Empty;
        }

        public List<DepartmentOption> Departments { get; private set; } = new();

        public IPagedList<SrvMaintenanceRequestModel> PagedRequests { get; private set; } = null!;

        public IndexInTimeModel(
            JasMtzDbContext context,
            IMapper mapper,
            UserManager<JasUser> userManager)
        {
            _context = context;
            _mapper = mapper;
            _userManager = userManager;
        }

        public async Task<IActionResult> OnGetAsync(int? pageNumber)
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

            // Vyfiltrovat jen nevyřešené stavy
            var unresolvedStatuses = new List<int> 
            { 
                (int)StatusEnum.New, 
                (int)StatusEnum.InProgress, 
                (int)StatusEnum.ToConfirm, 
                (int)StatusEnum.Returned 
            };
            query = query.Where(r => unresolvedStatuses.Contains(r.Status));

            var entities = await query.ToListAsync();

            var mappedRequests = _mapper.Map<List<SrvMaintenanceRequestModel>>(entities);

            // Filtr požadavků, které MÁ termín a termín je V BUDOUCNOSTI nebo DNES (tedy jsou "v termínu")
            var inTimeRequests = new List<SrvMaintenanceRequestModel>();
            var today = DateTime.Today;

            foreach (var req in mappedRequests)
            {
                int cat = req.RepairCategoryAdmin ?? req.RepairCategory;
                int days = cat switch
                {
                    (int)RepairCategory.Light   => 60,
                    (int)RepairCategory.Serious => 30,
                    (int)RepairCategory.Urgent  => 5,
                    _                           => 0
                };

                if (days > 0)
                {
                    var due = req.CreatedDate.AddDays(days).Date;
                    if (due < today) // PO TERMÍNU
                    {
                        inTimeRequests.Add(req);
                    }
                }
            }

            Requests = inTimeRequests
                .OrderByDescending(r => r.CreatedDate)
                .ToList();

            PageNumber = pageNumber.GetValueOrDefault(PageNumber);
            if (PageNumber <= 0) PageNumber = 1;

            PagedRequests = Requests.ToPagedList(PageNumber, PageSize);
            Requests = PagedRequests.ToList();

            await LoadDepartmentsAsync();
            await LoadDepartmentFilterAsync();

            return Page();
        }

        private async Task LoadDepartmentsAsync()
        {
            if (Requests.Count == 0) return;

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
                    request.Department = name;
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
                .Select(d => new DepartmentOption { Id = d.Id, Name = d.Name ?? string.Empty })
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
            if (adminCategory.HasValue && adminCategory.Value != userCategory)
            {
                return $"{GetCategoryText(adminCategory.Value)} ({userCatText})"; 
            }
            return userCatText; 
        }

        public string GetFormattedRepairDateText(DateTime createdDate, int userCategory, int? adminCategory)
        {
            if (createdDate == default || userCategory == 0) return string.Empty;

            int GetDays(int cat) => cat switch
            {
                (int)RepairCategory.Light => 60,
                (int)RepairCategory.Serious => 30,
                (int)RepairCategory.Urgent => 5,
                _ => 0
            };

            int userDays = GetDays(userCategory);
            string userDateText = userDays > 0 ? createdDate.AddDays(userDays).ToString("dd.MM.yyyy") : string.Empty;

            if (adminCategory.HasValue && adminCategory.Value != userCategory)
            {
                int adminDays = GetDays(adminCategory.Value);
                string adminDateText = adminDays > 0 ? createdDate.AddDays(adminDays).ToString("dd.MM.yyyy") : string.Empty;
                return $"{adminDateText} ({userDateText})";
            }
            return userDateText;
        }
    }
}
