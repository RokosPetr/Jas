using AutoMapper;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
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
    public class CategoryModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly UserManager<JasUser> _userManager;

        public List<MtzCategory> Categories { get; set; }
        public List<MTZ.Product> Products { get; set; }

        public CategoryModel(JasMtzDbContext context, IMapper mapper, UserManager<JasUser> userManager)
        {
            _context = context;
            _mapper = mapper;
            _userManager = userManager;
        }

        public async Task<IActionResult> OnGetAsync(int id)
        {
            Categories = new List<MtzCategory>();
            MtzCategory? mtzCategory = await _context.MtzCategories.FirstOrDefaultAsync(i => i.Id == id);
            while (mtzCategory != null)
            {
                Categories.Add(mtzCategory);
                mtzCategory = await _context.MtzCategories.FirstOrDefaultAsync(i => i.Id == mtzCategory.IdParent);
            }
            Categories = Categories.OrderBy(c => c.Level).ToList();

            List<MtzCategory> mtzCategories = await _context.MtzCategories.Where(i => i.IdParent == id || i.Id == id).ToListAsync();
            Products = new List<MTZ.Product>();
            foreach (MtzCategory category in mtzCategories)
            {
                List<MtzProduct> products = await _context.MtzProducts.Where(w => w.IdCategory == category.Id && (w.Filter & 1) == 1).ToListAsync();
                foreach (var item in products)
                {
                    MTZ.Product productMtz = _mapper.Map<MTZ.Product>(item);
                    Products.Add(productMtz);
                }
            }

            Products = Products.OrderBy(i => i.Name).ToList();

            return Page();
        }

        public async Task<IActionResult> OnPostSearchAsync(string searchString)
        {
            try
            {
                List<string> names = await _context.MtzProducts.Where(p => p.Name.Contains(searchString)).Select(p => p.Name).ToListAsync(); ;
                return new JsonResult(names);
            }
            catch (Exception ex)
            {
                return BadRequest();
            }
        }

    }
}
