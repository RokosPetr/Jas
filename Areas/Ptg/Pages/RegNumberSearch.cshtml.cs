using Jas.Data.JasDb;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "PTG - jas,PTG - vo")]
    public class RegNumberSearchModel : PageModel
    {
        private readonly JasDbContext _jasDb;

        public RegNumberSearchModel(JasDbContext jasDb)
        {
            _jasDb = jasDb;
        }

        public async Task<IActionResult> OnGetAsync(string q, CancellationToken ct)
        {
            if (string.IsNullOrWhiteSpace(q) || q.Length < 2)
                return new JsonResult(Array.Empty<object>());

            var results = await _jasDb.ViPtgRegNumbers
                .Where(r => r.RegNumber.Contains(q))
                .OrderBy(r => r.RegNumber)
                .Take(30)
                .Select(r => new { value = r.RegNumber, label = r.RegNumber + (r.Name != null ? " — " + r.Name.Trim() : "") })
                .ToListAsync(ct);

            return new JsonResult(results);
        }
    }
}
