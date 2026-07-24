using Jas.Data.JasDb;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "PTG - jas,PTG - vo")]
    public class RegNumberValidateModel : PageModel
    {
        private readonly JasDbContext _jasDb;

        public RegNumberValidateModel(JasDbContext jasDb)
        {
            _jasDb = jasDb;
        }

        // POST: /Ptg/RegNumberValidate
        // Body: { "numbers": ["1000446", "1000447", "NEEXISTUJE"] }
        // Response: { "found": [{value, label}], "notFound": ["NEEXISTUJE"] }
        public async Task<IActionResult> OnPostAsync([FromBody] ValidateRequest request, CancellationToken ct)
        {
            if (request?.Numbers == null || request.Numbers.Count == 0)
                return new JsonResult(new { found = Array.Empty<object>(), notFound = Array.Empty<string>() });

            var trimmed = request.Numbers
                .Select(n => n.Trim())
                .Where(n => !string.IsNullOrWhiteSpace(n))
                .Distinct(StringComparer.OrdinalIgnoreCase)
                .ToList();

            var matched = await _jasDb.ViPtgRegNumbers
                .Where(r => trimmed.Contains(r.RegNumber))
                .Select(r => new
                {
                    value = r.RegNumber,
                    label = r.RegNumber + (r.Name != null ? " \u2014 " + r.Name.Trim() : "")
                })
                .ToListAsync(ct);

            var foundValues = matched.Select(m => m.value).ToHashSet(StringComparer.OrdinalIgnoreCase);
            var notFound = trimmed.Where(n => !foundValues.Contains(n)).ToList();

            return new JsonResult(new { found = matched, notFound });
        }

        public class ValidateRequest
        {
            public List<string> Numbers { get; set; } = new();
        }
    }
}
