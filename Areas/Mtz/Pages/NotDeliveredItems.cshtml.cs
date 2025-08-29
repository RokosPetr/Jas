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
    [Authorize(Roles = "Administrator,MTZ - admin")]
    public class NotDeliveredItemsModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly IUserService _userService;
        private readonly IStoreService _storeService;
        private readonly IDepartmentService _departmentService;

        public List<MTZ.OrderItem> OrderItems { get; set; } = default!;
        [BindProperty(SupportsGet = true)]
        public string? SearchString { get; set; }
        [BindProperty(SupportsGet = true)]
        public string? Submit { get; set; }
        [BindProperty(SupportsGet = true)]
        public int PageNumber { get; set; } = 1;
        public NotDeliveredItemsModel(JasMtzDbContext context, IMapper mapper, IUserService userService, IStoreService storeService, IDepartmentService departmentService)
        {
            _context = context;
            _mapper = mapper;
            _userService = userService;
            _storeService = storeService;   
            _departmentService = departmentService;
        }

        public async Task<IActionResult> OnGetAsync()
        {
            if (Submit == "print")
            {
                return RedirectToPage("NotDeliveredPrint", new { SearchString = SearchString });
            }

            if (Submit == "cancel")
            {
                SearchString = null;
            }

            // Pøíprava dotazu
            IQueryable<MtzOrderItem> query = _context.MtzOrderItems
                    .Include(i => i.IdOrderNavigation)
                    .Include(i => i.IdProductNavigation)
                        .ThenInclude(p => p.MtzProductAttributes.Where(w => w.IdAttribute == 1))
                    .Where(i =>
                        (i.State == (int)ENUMS.OrderStates.Received || i.State == (int)ENUMS.OrderStates.Ordered) &&
                        i.IdOrderNavigation.State > (int)ENUMS.OrderStates.Received
                    ).OrderByDescending(o => o.IdOrderNavigation.OrderDate);

            if (!string.IsNullOrEmpty(SearchString))
            {
                SearchString = SearchString.ToLower().Trim();
                query = query.Where(i =>
                    i.IdProductNavigation.Code.ToLower().Contains(SearchString) ||
                    i.IdProductNavigation.Name.ToLower().Contains(SearchString) ||
                    i.IdProductNavigation.Description.ToLower().Contains(SearchString) ||
                    i.IdProductNavigation.Specification.ToLower().Contains(SearchString) ||
                    i.IdProductNavigation.MtzProductAttributes.Any(a =>
                        a.ProductCode != null &&
                        a.ProductCode.ToLower().Contains(SearchString)
                    )
                ).OrderByDescending(o => o.IdOrderNavigation.OrderDate);
            }

            // Stránkování
            var mtzOrderItems = await query.ToListAsync();

            await _userService.InitializeAsync();
            await _storeService.InitializeAsync();
            await _departmentService.InitializeAsync();

            OrderItems = mtzOrderItems.Select(item => _mapper.Map<MTZ.OrderItem>(item)).ToList();

            return Page();
        }
    }
}
