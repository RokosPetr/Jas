using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;

namespace Jas.Services.Pdf
{
    public interface INavbarServicePdf
    {
        public bool IsAdmin { get; }
        public bool IsExternalLoggedIn { get; }
        public JasUser? JasUser { get; }
        public string? UserId { get; }
    }

    public class NavbarServicePdf : INavbarServicePdf
    {
        private readonly JasMtzDbContext _dbContext;
        private readonly IUserService _userService;
        private Dictionary<int, int> _ordersCount;

        public NavbarServicePdf(JasMtzDbContext dbContext, IUserService userService)
        {
            _dbContext = dbContext;
            _userService = userService;
        }

        public bool IsAdmin
        {
            get
            {
                return _userService.IsAdmin("pdf");
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
