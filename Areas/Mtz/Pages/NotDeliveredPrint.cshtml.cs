using AutoMapper;
using Jas.Data.JasMtzDb;
using Jas.Services;
using Jas.Services.Mtz;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ENUMS = Jas.Globals.Mtz.Enums;
using MTZ = Jas.Models.Mtz;

namespace Jas.Areas.Mtz.Pages
{
    [Area("Mtz")]
    [Authorize(Roles = "Administrator,MTZ - admin,MTZ - uživatel")]
    public class NotDeliveredPrintModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly IUserService _userService;
        private readonly IStoreService _storeService;
        private readonly IDepartmentService _departmentService;

        public List<MTZ.OrderItem> OrderItems { get; set; }
        [BindProperty(SupportsGet = true)]
        public string? SearchString { get; set; }

        public MTZ.Order Order { get; set; }
        public NotDeliveredPrintModel(JasMtzDbContext context, IMapper mapper, IUserService userService, IStoreService storeService, IDepartmentService departmentService)
        {
            _context = context;
            _mapper = mapper;
            _userService = userService;
            _storeService = storeService;
            _departmentService = departmentService;
        }

        public async Task<IActionResult> OnGetAsync()
        {
            List<MtzOrderItem> mtzOrderItems;

            if (string.IsNullOrEmpty(SearchString))
            {
                mtzOrderItems = await _context.MtzOrderItems
                    .Include(i => i.IdOrderNavigation)
                    .Include(i => i.IdProductNavigation)
                        .ThenInclude(p => p.MtzProductAttributes.Where(w => w.IdAttribute == 1))
                    .Where(i => i.State == (int)ENUMS.OrderStates.Received || i.State == (int)ENUMS.OrderStates.Ordered)
                    .ToListAsync();
            }
            else
            {
                SearchString = SearchString.ToLower().Trim();
                mtzOrderItems = await _context.MtzOrderItems
                    .Include(i => i.IdOrderNavigation)
                    .Include(i => i.IdProductNavigation)
                        .ThenInclude(p => p.MtzProductAttributes.Where(w => w.IdAttribute == 1))
                    .Where(i =>
                        (i.State == (int)ENUMS.OrderStates.Received || i.State == (int)ENUMS.OrderStates.Ordered) &&
                        (
                            i.IdProductNavigation.Code.ToLower().Contains(SearchString) ||
                            i.IdProductNavigation.Name.ToLower().Contains(SearchString) ||
                            i.IdProductNavigation.MtzProductAttributes.Any(a =>
                                a.ProductCode != null &&
                                a.ProductCode.ToLower().Contains(SearchString))
                        )
                    )
                    .ToListAsync();
            }

            await _userService.InitializeAsync();
            await _storeService.InitializeAsync();
            await _departmentService.InitializeAsync();
            OrderItems = mtzOrderItems.Select(item => _mapper.Map<MTZ.OrderItem>(item)).ToList();
            
            return Page();

        }

    }
}
