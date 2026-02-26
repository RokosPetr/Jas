using System.Threading.Tasks;
using AutoMapper;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
using Jas.Models.Srv;
using Jas.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;

namespace Jas.Areas.Srv.Pages
{
    [Area("Srv")]
    [Authorize(Roles = "SRV - admin,SRV - user")]
    public class DetailModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly UserManager<JasUser> _userManager;
        private readonly IUserService _userService;

        [BindProperty]
        public SrvMaintenanceRequestModel Request { get; set; } = null!;

        public bool IsEdit => Request?.Id != 0;

        public string CreatedByName { get; private set; } = string.Empty;

        public DetailModel(
            JasMtzDbContext context,
            IMapper mapper,
            UserManager<JasUser> userManager,
            IUserService userService)
        {
            _context = context;
            _mapper = mapper;
            _userManager = userManager;
            _userService = userService;
        }

        public async Task<IActionResult> OnGetAsync(int? id)
        {
            if (!id.HasValue)
            {
                return NotFound();
            }

            var entity = await _context.SrvMaintenanceRequests
                .FirstOrDefaultAsync(r => r.Id == id.Value);

            if (entity == null)
            {
                return NotFound();
            }

            Request = _mapper.Map<SrvMaintenanceRequestModel>(entity);

            await LoadDepartmentAsync();
            await LoadCreatedByAsync();
            return Page();
        }

        private async Task LoadDepartmentAsync()
        {
            if (Request is null || Request.IdDepartment == 0)
            {
                return;
            }

            var department = await _context.JasDepartments
                .AsNoTracking()
                .FirstOrDefaultAsync(d => d.Id == Request.IdDepartment);

            Request.Department = department?.Name ?? string.Empty;
        }

        private async Task LoadCreatedByAsync()
        {
            if (Request is null || string.IsNullOrEmpty(Request.IdUser))
            {
                return;
            }

            var user = await _userManager.FindByIdAsync(Request.IdUser);
            CreatedByName = user?.Name ?? user?.UserName ?? string.Empty;
        }
    }
}