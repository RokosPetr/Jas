using AutoMapper;
using Jas.Data.JasMtzDb;
using Jas.Models.Mtz;
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
    public class OrderDetailModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly IStoreService _storeService;
        private readonly IDepartmentService _departmentService;


        public IUserService UserService;
        [BindProperty(SupportsGet = true)]
        public int? OrderId { get; set; }
        public Order Order { get; set; }

        public OrderDetailModel(JasMtzDbContext context, IMapper mapper, IUserService userService, IStoreService storeService, IDepartmentService departmentService)
        {
            _context = context;
            _mapper = mapper;
            UserService = userService;
            _storeService = storeService;
            _departmentService = departmentService;
        }

        public async Task<IActionResult> OnGetAsync()
        {
            if(OrderId is null)
            {
                return BadRequest();
            }

            await UserService.InitializeAsync();
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

        public async Task<IActionResult> OnPostUpdateItemStateAsync(MTZ.OrderItem orderItemMtz)
        {
            OrderId = orderItemMtz.Id;
            MtzOrderItem? mztOrderItem = await _context.MtzOrderItems.FirstOrDefaultAsync(i => i.Id == orderItemMtz.Id);
            MtzOrder? mztOrder = await _context.MtzOrders.Include(i => i.MtzOrderItems).FirstOrDefaultAsync(i => i.Id == orderItemMtz.IdOrder);

            if (mztOrderItem is not null && mztOrder is not null)
            {
                mztOrderItem.State = orderItemMtz.State;
                int processedCount = mztOrder.MtzOrderItems.Count(i => i.State == ((int)ENUMS.OrderStates.Processed));
                int canceledItemCount = mztOrder.MtzOrderItems.Count(i => i.State == ((int)ENUMS.OrderStates.Cancelled));
                int orderedItemCount = mztOrder.MtzOrderItems.Count(i => i.State == ((int)ENUMS.OrderStates.Ordered));

                if (canceledItemCount == mztOrder.MtzOrderItems.Count)
                {
                    mztOrder.State = ((int)ENUMS.OrderStates.Cancelled);
                }
                else if (processedCount == mztOrder.MtzOrderItems.Count)
                {
                    mztOrder.State = ((int)ENUMS.OrderStates.Processed);
                }
                else if (canceledItemCount + processedCount == mztOrder.MtzOrderItems.Count){
                    mztOrder.State = ((int)ENUMS.OrderStates.ProcessedCancelled);
                }
                else if (canceledItemCount + processedCount + orderedItemCount > 0)
                {
                    mztOrder.State = ((int)ENUMS.OrderStates.InProgress);
                }
                else if (canceledItemCount + processedCount + orderedItemCount == 0)
                {
                    mztOrder.State = ((int)ENUMS.OrderStates.Received);
                }

                _context.SaveChanges();
            }

            return Partial("_Navbar");
        }

        public async Task<IActionResult> OnPostUpdateOrderItemAsync(MTZ.OrderItem orderItemMtz)
        {
            MtzOrderItem? orderItem = await _context.MtzOrderItems.FirstOrDefaultAsync(i => i.Id == orderItemMtz.Id);
            if (orderItem != null)
            {
                orderItem.Comment = orderItemMtz.Comment;
                _context.SaveChanges();
            }
            return new EmptyResult();
        }


    }
}
