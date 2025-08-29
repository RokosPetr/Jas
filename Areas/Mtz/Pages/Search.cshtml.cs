using AutoMapper;
using Jas.Data.JasMtzDb;
using Jas.Models.Mtz;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using MTZ = Jas.Models.Mtz;

namespace Jas.Areas.Mtz.Pages
{
    [Area("Mtz")]
    [Authorize(Roles = "Administrator,MTZ - admin,MTZ - uživatel")]
    public class SearchModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;

        public List<MTZ.Product> Products { get; set; }
        [BindProperty(SupportsGet = true)]
        public string? SearchString { get; set; }

        public SearchModel(JasMtzDbContext context, IMapper mapper)
        {
            _context = context;
            _mapper = mapper;
        }

        public async Task<IActionResult> OnGetAsync()
        {
            SearchString = SearchString.ToLower().Trim();
            List<MtzProduct> mtzProducts = await _context.MtzProducts
                .Include(i => i.MtzProductAttributes.Where(w => w.IdAttribute == 1))
            .Where(i =>
                        i.Code.ToLower().Contains(SearchString) ||
                        i.Name.ToLower().Contains(SearchString) ||
                        i.Description.ToLower().Contains(SearchString) ||
                        i.Specification.ToLower().Contains(SearchString) ||
                        i.MtzProductAttributes.Any(a =>
                            a.ProductCode != null &&
                            a.ProductCode.ToLower().Contains(SearchString))
                    )
                .OrderBy(o => o.Name).ToListAsync();


            Products = new List<MTZ.Product>(mtzProducts.Select(item => _mapper.Map<MTZ.Product>(item)).ToList());

            if(Products.Count == 1)
            {
                Product? product = Products.FirstOrDefault();
                string? size = product.MtzProductAttributes.Where(f => f.ProductCode.ToLower().Contains(SearchString)).Select(s => s.Value).FirstOrDefault();
                return RedirectToPage("Product", new { Id = product.Id, AttrSize = size });
            }
            
            return Page();
        }

        public async Task<IActionResult> OnGetAutoCompleteAsync(string searchString)
        {
            try
            {
                List<string> names = await _context.MtzProducts.Where(p => p.Name.Contains(searchString) && (p.Filter & 1) == 1).Select(p => p.Name).ToListAsync(); ;
                return new JsonResult(names);
            }
            catch (Exception ex)
            {
                return BadRequest();
            }
        }

    }
}
