using AutoMapper;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
using Jas.Infrastructure.Ptg;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;

namespace Jas.Areas.Pdf.Pages
{
    [Area("Pdf")]
    //[Authorize]
    [Authorize(Roles = "PDF - admin")]
    public class IndexModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly UserManager<JasUser> _userManager;
        private readonly IStandSearchReader _searchReader;

        [BindProperty(SupportsGet = true)]
        public string?CompanyKey { get; set; }
        public List<ViPdfCompanyCatalog> Catalogs { get; set; } = new();

        public IndexModel(JasMtzDbContext context, IMapper mapper, UserManager<JasUser> userManager, IStandSearchReader searchReader)
        {
            _context = context;
            _mapper = mapper;
            _userManager = userManager;
            _searchReader = searchReader;
        }

        public async Task<IActionResult> OnGetAsync(string? CompanyKey)
        {

            if (string.IsNullOrEmpty(CompanyKey))
            {
                return RedirectToPage(null, new { CompanyKey = "partneri" });
            }

            Catalogs = await _context.ViPdfCompanyCatalogs
                .Where(c => c.CompanyKey == CompanyKey)
                .ToListAsync();
            return Page();
        }

    }
}
