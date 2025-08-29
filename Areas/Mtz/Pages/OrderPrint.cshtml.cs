using AutoMapper;
using Jas.Data.JasMtzDb;
using Jas.Models.Mtz;
using Jas.Services;
using Jas.Services.Mtz;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using MTZ = Jas.Models.Mtz;

namespace Jas.Areas.Mtz.Pages
{
    [Area("Mtz")]
    [Authorize(Roles = "Administrator,MTZ - admin,MTZ - uživatel")]
    public class OrderPrintModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly IStoreService _storeService;
        private readonly IUserService _userService;
        private readonly IDepartmentService _departmentService;

        public List<MTZ.OrderItem> OrderItems { get; set; }

        [BindProperty(SupportsGet = true)]
        public int? OrderId { get; set; }
        public MTZ.Order Order { get; set; }
        public OrderPrintModel(JasMtzDbContext context, IMapper mapper, IUserService userService, IStoreService storeService, IDepartmentService departmentService)
        {
            _context = context;
            _mapper = mapper;
            _userService = userService;
            _storeService = storeService;
            _departmentService = departmentService;

        }

        public async Task<IActionResult> OnGetAsync()
        {
            if (OrderId is null)
            {
                return BadRequest();
            }

            await _userService.InitializeAsync();
            await _storeService.InitializeAsync();
            await _departmentService.InitializeAsync();

            MtzOrder? mtzOrder = await _context.MtzOrders
                .Include(i => i.MtzOrderItems)
                .ThenInclude(i => i.IdProductNavigation)
                .ThenInclude(p => p.MtzProductAttributes.Where(w => w.IdAttribute == 1))
                .FirstOrDefaultAsync(o => o.Id == (int)OrderId);

            if (mtzOrder is null)
            {
                return NotFound();
            }

            Order = _mapper.Map<Order>(mtzOrder);

            return Page();
        }

    }
}
