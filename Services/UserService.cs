using Jas.Data.JasIdentityApp;
using Jas.Data.JasIdentityDb;
using Jas.Data.JasMtzDb;
using Microsoft.AspNetCore.Identity;
using Microsoft.EntityFrameworkCore;
using System.Security.Claims;

namespace Jas.Services
{
    public interface IUserService
    {
        Task InitializeAsync();
        AspNetUser? GetUser(string userId);
        public bool IsInRole(string role);
        public bool IsExternalLoggedIn { get; }
        public string? UserId { get; }
        public JasUser? JasUser { get; }
        public bool IsMtzAdmin { get; }
        public bool IsAdmin(string area);
    }
    public class UserService : IUserService
    {
        private readonly JasIdentityDbContext _dbContext;
        private readonly IHttpContextAccessor _httpContextAccessor;
        private readonly UserManager<JasUser> _userManager;
        private Dictionary<string, AspNetUser> _users;

        public UserService(JasIdentityDbContext dbContext, IHttpContextAccessor httpContextAccessor, UserManager<JasUser> userManager)
        {
            _dbContext = dbContext;
            _httpContextAccessor = httpContextAccessor;
            _userManager = userManager;
        }

        public async Task InitializeAsync()
        {
            if (_users is null)
            {
                var users = await _dbContext.AspNetUsers.ToListAsync();
                _users = users.ToDictionary(u => u.Id, u => u);
            }
        }

        public AspNetUser? GetUser(string userId)
        {
            
            if (_users is null) return null;

            return _users.TryGetValue(userId, out var user) ? user : null;
        }

        public bool IsInRole(string role)
        {
            return _httpContextAccessor.HttpContext?.User?.IsInRole(role) ?? false;
        }

        public bool IsMtzAdmin
        {
            get
            {
                return _httpContextAccessor.HttpContext?.User?.IsInRole("MTZ - admin") ?? false;
            }
        }

        public bool IsAdmin(string area)
        {
            return _httpContextAccessor.HttpContext?.User?.IsInRole($"{area} - admin") ?? false;
        }

        public bool IsExternalLoggedIn
        {
            get
            {
                return _httpContextAccessor.HttpContext.Session.Keys.Contains("ExternalLoggedIn");
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
                if (UserId == null)
                    return null;

                return _userManager.Users.FirstOrDefault(u => u.Id == UserId);
            }
        }

    }

}
