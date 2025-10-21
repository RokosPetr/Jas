using AutoMapper;
using Jas.Data.JasMtzDb;
using Jas.Models.Mtz;
using Jas.Services;
using Jas.Services.Mtz;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.AspNetCore.Mvc.Rendering;
using Microsoft.EntityFrameworkCore;
using X.PagedList;
using X.PagedList.EF;
using ENUMS = Jas.Globals.Mtz.Enums;

namespace Jas.Areas.Mtz.Pages
{
    [Area("Mtz")]
    [Authorize(Roles = "Administrator,MTZ - admin,MTZ - uživatel")]
    public class OrderModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly IStoreService _storeService;
        private readonly IDepartmentService _departmentService;


        public IUserService UserService;
        [BindProperty(SupportsGet = true)]
        public int? State 
        {
            get
            {
                switch (OrderStatus)
                {
                    case "received":
                        return 1;
                    case "inprogress":
                        return 2;
                    case "tosend":
                        return 3;
                    case "processed":
                        return 4;
                    case "canceled":
                        return 5;
                    default:
                        return null;
                }
            }
        }
        [BindProperty(SupportsGet = true)]
        public string? OrderStatus { get; set; }
        [BindProperty(SupportsGet = true)]
        public bool Detailed { get; set; } = false;
        [BindProperty(SupportsGet = true)]
        public bool NewOrder { get; set; } = false;
        public IPagedList<Order> Orders { get; set; }
        [BindProperty(SupportsGet = true)]
        public int PageNumber { get; set; } = 1;
        public SelectList DepartmentSelectList { get; set; }
        [BindProperty(SupportsGet = true)]
        public int? DepartmentId { get; set; }
        [BindProperty(SupportsGet = true)]
        public bool FormSubmitted { get; set; } = false;

        public OrderModel(JasMtzDbContext context, IMapper mapper, IUserService userService, IStoreService storeService, IDepartmentService departmentService)
        {
            _context = context;
            _mapper = mapper;
            UserService = userService;
            _storeService = storeService;
            _departmentService = departmentService;

        }

        public async Task<IActionResult> OnGetAsync()
        {
            if (FormSubmitted)
            {
                return RedirectToPage(null, new
                {
                    DepartmentId = DepartmentId,
                    PageNumber = 1
                });
            }
            
            await UserService.InitializeAsync();
            await _storeService.InitializeAsync();
            await _departmentService.InitializeAsync();

            var departments = await _context.JasDepartments
                .OrderBy(d => d.Name) // nebo jiné pole
                .ToListAsync();

            int[] sendToDepartments = { 1, 2, 3, 5, 6, 7, 8 };

            if (UserService.IsMtzAdmin)
            {
                DepartmentSelectList = new SelectList(departments, "Id", "Name");
            }
            else if (UserService.IsInRole("MTZ - shipper") && State == 3)
            {
                DepartmentSelectList = new SelectList(departments.Where(w => sendToDepartments.Contains((int)w.Id!)), "Id", "Name");  
            }

            if (NewOrder)
            {
                TempData["messageInfo"] = "Vaše objednávka byla odeslána.";
            }

            // Pøíprava dotazu
            IQueryable<MtzOrder> query = _context.MtzOrders
                .Include(i => i.MtzOrderItems)
                    .ThenInclude(p => p.IdProductNavigation)
                .Include(i => i.IdDepartmentNavigation);

            // Oprávnìní
            if (State != (int)ENUMS.OrderStates.ToSend)
            {
                int[] allowedStates;
                if (State == (int)ENUMS.OrderStates.Processed)
                {
                    allowedStates = new[] {
                        (int)ENUMS.OrderStates.Processed,
                        (int)ENUMS.OrderStates.ProcessedCancelled,
                        (int)ENUMS.OrderStates.Cancelled
                    };
                }
                else
                {
                    allowedStates = new[] {
                        (int)State!
                    };
                }
                query = query.Where(o => allowedStates.Contains(o.State));
                if (!UserService.IsMtzAdmin)
                {
                    query = query.Where(o => o.IdUser == UserService.UserId);
                }
            }
            else if(UserService.IsInRole("MTZ - shipper") && State == 3)
            {
                int[] allowedStates = new[] {
                    (int)ENUMS.OrderStates.Processed,
                    (int)ENUMS.OrderStates.InProgress
                };

                query = query.Where(w =>
                    allowedStates.Contains(w.State) 
                    && w.SentDate == null 
                    && sendToDepartments.Contains((int)w.IdDepartment!) 
                    && w.OrderDate >= new DateTime(2025, 5, 1) 
                    && w.MtzOrderItems.Any(item => item.State == (int)ENUMS.OrderStates.Processed)
                );
            }

            // Aplikuj filtr podle oddìlení
            if (DepartmentId.HasValue)
            {
                query = query.Where(o => o.IdDepartment == DepartmentId.Value);
            }

            // Stránkování
            var mtzOrders = await query
                .OrderByDescending(o => o.OrderDate)
                .ToPagedListAsync(PageNumber, 10);

            // Mapování
            Orders = new StaticPagedList<Order>(
                mtzOrders.Select(item => _mapper.Map<Order>(item)).ToList(),
                mtzOrders.PageNumber,
                mtzOrders.PageSize,
                mtzOrders.TotalItemCount
            );

            //Orders = mtzOrders.Select(item => _mapper.Map<Order>(item)).ToList();

            return Page();
        }

        public async Task<IActionResult> OnPostUpdateOrderAsync(Order order, string field)
        {
            MtzOrder? Order = await _context.MtzOrders.FirstOrDefaultAsync(i => i.Id == order.Id);
            if (Order != null)
            {
                if (field.Equals("Comment")) Order.Comment = order.Comment;
                if (field.Equals("SentDate")) Order.SentDate = order.SentDate;
                _context.SaveChanges();
            }

            return Partial("_Navbar");
            //return new EmptyResult();

        }

    }
}
