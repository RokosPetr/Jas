using AutoMapper;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
using Jas.Services.Mtz;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;

namespace Jas.Areas.Mtz.Pages
{
    [Area("Mtz")]
    [Authorize(Roles = "Administrator,MTZ - admin,MTZ - uživatel")]
    public class IndexModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly UserManager<JasUser> _userManager;

        public List<MtzCategory> Categories { get; set; }

        public IndexModel(JasMtzDbContext context, IMapper mapper, UserManager<JasUser> userManager)
        {
            _context = context;
            _mapper = mapper;
            _userManager = userManager;
        }

        public async Task<IActionResult> OnGetAsync()
        {
            //var _user = await _userManager.FindByEmailAsync("vavra@koupelny-jas.cz");

            //if(_user == null)
            //{
            //    JasUser jasUser = new JasUser();
            //    jasUser.Email = "vavra@koupelny-jas.cz";
            //    jasUser.UserName = "vavra-libor";
            //    jasUser.StoreId = 3;
            //    jasUser.Name = "Vávra Libor";
            //    jasUser.InternalLogin = "3x2";
            //    jasUser.EmailConfirmed = false;
            //    jasUser.CreatedAt = System.DateTime.Now;
            //    await _userManager.CreateAsync(jasUser);
            //}

            Categories = await _context.MtzCategories.Include(i => i.MtzProducts).ToListAsync();

            return Page();
        }

    }
}
