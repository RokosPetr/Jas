using AutoMapper;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
using Jas.Models.Mtz;
using Jas.Services.Mtz;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using MTZ = Jas.Models.Mtz;

namespace Jas.Areas.Mtz.Pages
{
    [Area("Mtz")]
    [Authorize(Roles = "Administrator,MTZ - admin,MTZ - uživatel")]
    public class ProductModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly UserManager<JasUser> _userManager;

        public MTZ.Product Product { get; set; }
        [BindProperty(SupportsGet = true)]
        public int? Id { get; set; }
        [BindProperty(SupportsGet = true)]
        public string? AttrSize { get; set; }

        public List<MtzCategory> Categories { get; set; }

        public ProductModel(JasMtzDbContext context, IMapper mapper, UserManager<JasUser> userManager)
        {
            _context = context;
            _mapper = mapper;
            _userManager = userManager;
        }

        public async Task<IActionResult> OnGetAsync()
        {
            if (Id is null)
            {
                return BadRequest();
            }

            MtzProduct? mtzProduct = await _context.MtzProducts.Include(i => i.MtzProductAttributes.Where(w => w.IdAttribute == 1)).FirstOrDefaultAsync(i => i.Id == Id && (i.Filter & 1) == 1);

            if (mtzProduct == null)
            {
                return NotFound();
            }

            Categories = new List<MtzCategory>();
            MtzCategory? mtzCategory = await _context.MtzCategories.FirstOrDefaultAsync(i => i.Id == mtzProduct.IdCategory);
            while (mtzCategory != null)
            {
                Categories.Add(mtzCategory);
                mtzCategory = await _context.MtzCategories.FirstOrDefaultAsync(i => i.Id == mtzCategory.IdParent);
            }
            Categories = Categories.OrderBy(c => c.Level).ToList();

            Product = _mapper.Map<MTZ.Product>(mtzProduct);

            return Page();
        }

    }
}
