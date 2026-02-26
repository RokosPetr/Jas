using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
using Microsoft.EntityFrameworkCore;
using ENUMS = Jas.Globals.Srv.Enums;

namespace Jas.Services.Srv
{
    public interface INavbarServiceSrv
    {
        Task InitializeAsync();
        int? GetRequestCountByStatus(int status);
        bool IsAdmin { get; }
        bool IsExternalLoggedIn { get; }
        JasUser? JasUser { get; }
        string? UserId { get; }
    }

    public class NavbarServiceSrv : INavbarServiceSrv
    {
        private readonly JasMtzDbContext _dbContext;
        private readonly IUserService _userService;
        private Dictionary<int, int>? _requestsCount;

        public NavbarServiceSrv(JasMtzDbContext dbContext, IUserService userService)
        {
            _dbContext = dbContext;
            _userService = userService;
        }

        public async Task InitializeAsync()
        {
            if (_requestsCount is null)
            {
                // základní dotaz na požadavky
                var query = _dbContext.SrvMaintenanceRequests.AsQueryable();

                // pokud není SRV admin, počítat jen požadavky přihlášeného uživatele
                if (!_userService.IsAdmin("SRV"))
                {
                    var userId = _userService.UserId;
                    if (!string.IsNullOrEmpty(userId))
                    {
                        query = query.Where(r => r.IdUser == userId);
                    }
                    else
                    {
                        // bez identifikace uživatele nevracej nic
                        query = query.Where(r => false);
                    }
                }

                // počty požadavků podle Status (New, InProgress, Resolved, Cancelled)
                _requestsCount = await query
                    .GroupBy(r => r.Status)
                    .ToDictionaryAsync(g => (int)g.Key, g => g.Count());
            }
        }

        public int? GetRequestCountByStatus(int status)
        {
            if (_requestsCount is null)
            {
                return null;
            }

            return _requestsCount.TryGetValue(status, out var count)
                ? (count == 0 ? null : count)
                : null;
        }

        public bool IsAdmin => _userService.IsAdmin("SRV");

        public bool IsExternalLoggedIn => _userService.IsExternalLoggedIn;

        public string? UserId => _userService.UserId;

        public JasUser? JasUser => _userService.JasUser;
    }
}