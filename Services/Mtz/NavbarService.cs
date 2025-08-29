using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
using Microsoft.EntityFrameworkCore;
using ENUMS = Jas.Globals.Mtz.Enums;

namespace Jas.Services.Mtz
{
    public interface INavbarServiceMtz
    {
        Task InitializeAsync();
        public int? GetOrderCountByState(int state);
        public bool IsAdmin { get; }
        public bool IsExternalLoggedIn { get; }
        public JasUser? JasUser { get; }
        public string? UserId { get; }
    }

    public class NavbarServiceMtz : INavbarServiceMtz
    {
        private readonly JasMtzDbContext _dbContext;
        private readonly IUserService _userService;
        private Dictionary<int, int> _ordersCount;

        public NavbarServiceMtz(JasMtzDbContext dbContext, IUserService userService)
        {
            _dbContext = dbContext;
            _userService = userService;
        }

        public async Task InitializeAsync()
        {
            if (_ordersCount is null)
            {
                _ordersCount = await _dbContext.MtzOrders.Where(i => 
                    (i.State > (int)ENUMS.OrderStates.InCart && IsAdmin) ||
                    (i.State > (int)ENUMS.OrderStates.InCart && !IsAdmin && i.IdUser == UserId)
                ).Select(t => new { t.State }).GroupBy(g => g.State).ToDictionaryAsync(t => t.Key, t => t.Count());

                _ordersCount.Add((int)ENUMS.OrderStates.NotDeliveredItem, await _dbContext.MtzOrderItems.Where(w => w.State == (int)ENUMS.OrderStates.Received || w.State == (int)ENUMS.OrderStates.Ordered).CountAsync());
                _ordersCount.Add((int)ENUMS.OrderStates.InCart, await _dbContext.MtzOrderItems.Where(w => w.State == (int)ENUMS.OrderStates.InCart && w.IdOrderNavigation.IdUser == UserId).CountAsync());

                int[] sendToDepartments = { 1, 2, 3, 5, 6, 7, 8 };
                int[] allowedStates = new[] {
                    (int)ENUMS.OrderStates.Processed,
                    (int)ENUMS.OrderStates.InProgress
                };
                
                _ordersCount.Add((int)ENUMS.OrderStates.ToSend, await _dbContext.MtzOrders
                    .Where(w =>
                    allowedStates.Contains(w.State)
                    && w.SentDate == null 
                    && sendToDepartments.Contains((int)w.IdDepartment!) 
                    && w.OrderDate >= new DateTime(2025, 5, 1)
                    && w.MtzOrderItems.Any(item => item.State == (int)ENUMS.OrderStates.Processed)
                ).CountAsync());

                _ordersCount[(int)ENUMS.OrderStates.Processed] =
                    _ordersCount.GetValueOrDefault((int)ENUMS.OrderStates.Processed) +
                    _ordersCount.GetValueOrDefault((int)ENUMS.OrderStates.ProcessedCancelled);

            }
        }

        public int? GetOrderCountByState(int state)
        {
            if (_ordersCount is null) return null;

            return _ordersCount.TryGetValue(state, out var count) ? count : null;
        }
        public bool IsAdmin
        {
            get
            {
                return _userService.IsMtzAdmin;
            }
        }

        public bool IsExternalLoggedIn
        {
            get
            {
                return _userService.IsExternalLoggedIn;
            }
        }

        public string? UserId
        {
            get
            {
                return _userService.UserId;
            }
        }

        public JasUser? JasUser
        {
            get
            {
                return _userService.JasUser;
            }
        }

    }

}
