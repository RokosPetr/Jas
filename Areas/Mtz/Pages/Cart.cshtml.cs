using AutoMapper;
using Jas.Data.JasMtzDb;
using MTZ = Jas.Models.Mtz;
using Jas.Services.Mtz;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using ENUMS = Jas.Globals.Mtz.Enums;
using Jas.Data.JasIdentityApp;
using System.Security.Claims;
using Jas.Services;
using Jas.Models.Mtz;

namespace Jas.Areas.Mtz.Pages
{
    [Area("Mtz")]
    [Authorize(Roles = "Administrator,MTZ - admin,MTZ - uživatel")]
    public class CartModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly UserManager<JasUser> _userManager;
        private readonly IUserService _userService;


        [BindProperty(SupportsGet = true)]
        public int NewOrderId { get; set; }
        [BindProperty(SupportsGet = true)]
        public int OrderId { get; set; }
        public Order Order { get; set; }

        public CartModel(JasMtzDbContext context, IMapper mapper, UserManager<JasUser> userManager, IUserService userService)
        {
            _context = context;
            _mapper = mapper;
            _userManager = userManager;
            _userService = userService;
        }

        public async Task<IActionResult> OnGetAsync()
        {
            await _userService.InitializeAsync();
            MtzOrder? mtzOrder = await _context.MtzOrders
                .Include(i => i.MtzOrderItems)
                .ThenInclude(i => i.IdProductNavigation)
                .ThenInclude(p => p.MtzProductAttributes.Where(w => w.IdAttribute == 1))
                .FirstOrDefaultAsync(o => o.IdUser == _userService.UserId && o.State == 0);

            if (mtzOrder == null)
            {
                TempData["messageInfo"] = "Vaš košík je prázdný.";
                return Page();
            }

            Order = _mapper.Map<Order>(mtzOrder);
            OrderId = Order.Id;

            return Page();
        }

        public async Task<IActionResult> OnPostOrderCreateAsync()
        {
            MtzOrder? mtzOrder = await _context.MtzOrders.Include(i => i.MtzOrderItems).FirstOrDefaultAsync(i => i.Id == OrderId);
            if (mtzOrder != null)
            {
                mtzOrder.State = (int)ENUMS.OrderStates.Received;
                mtzOrder.OrderDate = DateTime.Now;
                mtzOrder.StoreId = _userService.JasUser.StoreId;
                mtzOrder.IdDepartment = _userService.JasUser.DepartmentId;
                foreach (MtzOrderItem item in mtzOrder.MtzOrderItems)
                {
                    item.State = (int)ENUMS.OrderStates.Received;
                }
                _context.SaveChanges();
                return RedirectToPage("/Order", new
                {
                    area = "Mtz",
                    OrderStatus = "received",
                    PageNumber = 1,
                    NewOrder = true
                });
            }
            else
            {
                return Page();
            }
        }

        public async Task<IActionResult> OnPostAddItemAsync(MTZ.Product productMtz)
        {

            MtzProduct? product = await _context.MtzProducts.FirstOrDefaultAsync(i => i.Id == productMtz.Id);

            if (product != null)
            {
                MtzOrder? order = await _context.MtzOrders.Include(i => i.MtzOrderItems).FirstOrDefaultAsync(o => o.State == 0 && o.IdUser == _userService.JasUser.Id);

                if (order == null)
                {
                    try
                    {
                        order = new MtzOrder()
                        {
                            IdUser = _userService.UserId,
                            UserEmail = _userService.JasUser.Email,
                            OrderDate = DateTime.Now,
                            State = 0,
                            UserName = _userService.JasUser.Name
                        };
                        _context.MtzOrders.Add(order);
                        _context.SaveChanges();


                    }
                    catch (Exception e)
                    {
                    }
                }

                try
                {

                    MtzOrderItem? orderItem = order.MtzOrderItems.FirstOrDefault(i => i.IdProduct == productMtz.Id);

                    if (orderItem == null || !(orderItem.SelectedSize == null || orderItem.SelectedSize.Equals(productMtz.SelectedSize)))
                    {
                        orderItem = new MtzOrderItem()
                        {
                            IdOrder = order.Id,
                            IdProduct = product.Id,
                            Amount = productMtz.Amount,
                            Name = product.Name,
                            OrderUnit = product.OrderUnit,
                            PackageSize = (int)product.PackageSize,
                            SelectedSize = productMtz.SelectedSize,
                            NamesOfEmployees = productMtz.NamesOfEmployees  
                        };
                        _context.MtzOrderItems.Add(orderItem);

                    }
                    else if (productMtz.Amount > 0)
                    {
                        orderItem.Amount = orderItem.Amount + productMtz.Amount;
                        orderItem.NamesOfEmployees = productMtz.NamesOfEmployees + ", " + orderItem.NamesOfEmployees;
                    }

                    _context.SaveChanges();


                }
                catch (Exception e)
                {
                }
            }

            return Partial("_Navbar");
        }

        public async Task<IActionResult> OnPostUpdateItemAsync(MTZ.OrderItem orderItemMtz, string field)
        {
            MtzOrderItem? mtzOrderItem = await _context.MtzOrderItems.FirstOrDefaultAsync(i => i.Id == orderItemMtz.Id);
            
            if (mtzOrderItem != null )
            {

                switch (field)
                {
                    case "AmountUpdate":
                        if (orderItemMtz.AmountUpdate == 0)
                        {
                            _context.MtzOrderItems.Remove(mtzOrderItem);
                        }
                        else
                        {
                            mtzOrderItem.Amount = (int)orderItemMtz.AmountUpdate;
                        }
                        _context.SaveChanges();
                        break;

                    case "SelectedSize":
                        MtzOrderItem? mtzOrderItemSize = await _context.MtzOrderItems.FirstOrDefaultAsync(i => i.IdOrder == orderItemMtz.IdOrder && i.SelectedSize.Equals(orderItemMtz.Product.SelectedSize));
                        if (mtzOrderItemSize != null && mtzOrderItemSize.Id != mtzOrderItem.Id)
                        {
                            mtzOrderItemSize.Amount = mtzOrderItem.Amount + mtzOrderItemSize.Amount;
                            mtzOrderItemSize.NamesOfEmployees = mtzOrderItem.NamesOfEmployees + ", " + mtzOrderItemSize.NamesOfEmployees;
                            _context.MtzOrderItems.Remove(mtzOrderItem);
                        }
                        else
                        {
                            mtzOrderItem.SelectedSize = orderItemMtz.Product.SelectedSize;
                        }
                        _context.SaveChanges();
                        break;
                }

            }

            await _userService.InitializeAsync();
            MtzOrder? mtzOrder = await _context.MtzOrders
                .Include(i => i.MtzOrderItems)
                .ThenInclude(i => i.IdProductNavigation)
                .ThenInclude(p => p.MtzProductAttributes.Where(w => w.IdAttribute == 1))
                .FirstOrDefaultAsync(o => o.IdUser == _userService.UserId && o.State == 0);

            Order = _mapper.Map<Order>(mtzOrder);
            OrderId = Order.Id;

            ModelState.Clear();
            return Page();
        }

    }
}
