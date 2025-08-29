using AutoMapper;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
using Jas.Globals.Mtz;
using Jas.Models.Mtz;
using Microsoft.AspNetCore.Identity;
using Microsoft.EntityFrameworkCore;
using System.Security.Claims;

namespace Jas.Services.Mtz
{
    public interface IOrderService
    {
        public List<Order> Orders { get; }
        public Dictionary<int, int?> OrdersItemsCount { get; }
        public JasUser? JasUser { get; }
        public string? UserId { get; }
        public bool IsAdmin { get; }
        public ClaimsPrincipal User { get; set; }
        public bool DetailedList { get; set; }
        public bool IsExternalLoggedIn { get; }

    }

    public class OrderService : IOrderService
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly UserManager<JasUser> _userManager;
        private readonly RoleManager<IdentityRole> _roleManager;
        private readonly IHttpContextAccessor _httpContextAccessor;
        private readonly IUserService _userService;
        private readonly IStoreService _storeService;
        private readonly IDepartmentService _departmentService;


        private List<Order> _orders;

        public OrderService(JasMtzDbContext context, IMapper mapper, UserManager<JasUser> userManager, RoleManager<IdentityRole> roleManager, IHttpContextAccessor httpContextAccessor, IUserService userService, IStoreService storeService, IDepartmentService departmentService)
        {
            _context = context;
            _mapper = mapper;
            _userManager = userManager;
            _roleManager = roleManager;
            _httpContextAccessor = httpContextAccessor;
            _userService = userService;
            _storeService = storeService;
            _departmentService = departmentService;

        }

        public List<Order> Orders {
            get
            {
                if (_orders == null)
                {
                    List<MtzOrder> mtzOrders;
                    if (IsAdmin)
                    {
                        mtzOrders = _context.MtzOrders.Include(i => i.MtzOrderItems).OrderByDescending(o => o.OrderDate).ToList();
                    }
                    else
                    {
                        mtzOrders = _context.MtzOrders.Include(i => i.MtzOrderItems).Where(o => o.IdUser == UserId).OrderByDescending(o => o.OrderDate).ToList();
                    }

                    _orders = new List<Order>();
                    foreach (MtzOrder item in mtzOrders)
                    {
                        Order Order = _mapper.Map<Order>(item);
                        Order.DbContext = _context;
                        _orders.Add(Order);
                        if (DetailedList)
                        {
                            Order.OrderItems = new List<OrderItem>();
                            foreach (MtzOrderItem mtzOrderItem in Order.MtzOrderItems)
                            {
                                OrderItem orderItem = _mapper.Map<OrderItem>(mtzOrderItem);
                                orderItem.Product = _mapper.Map<Product>(_context.MtzProducts.FirstOrDefault(i => i.Id == orderItem.IdProduct));
                                orderItem.IdOrderItem = orderItem.Id;
                                Order.OrderItems.Add(orderItem);
                            }
                        }
                    }

                }
                return _orders;
            }
        }
        public Dictionary<int, int?> OrdersItemsCount {
            get
            {
               return UserOrders.GetItemsCount(Orders, IsAdmin, UserId);
            }
        }
        public string? UserId
        {
            get
            {
                var user = _httpContextAccessor.HttpContext?.User;
                return user?.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            }
        }

        public JasUser? JasUser
        {
            get
            {
                var userId = UserId;
                if (userId == null)
                    return null;

                return _userManager.Users.FirstOrDefault(u => u.Id == userId);
            }
        }
        public bool IsAdmin {
            get 
            {
                return _httpContextAccessor.HttpContext?.User?.IsInRole("MTZ - admin") ?? false;
            }
        }

        public bool IsExternalLoggedIn
        {
            get
            {
                return _httpContextAccessor.HttpContext.Session.Keys.Contains("ExternalLoggedIn"); 
            }
        }

        public ClaimsPrincipal User { get; set; }
        public bool DetailedList { get; set; }
    }

}
