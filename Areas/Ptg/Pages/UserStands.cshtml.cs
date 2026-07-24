using Jas.Data.JasDb;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
using Jas.Data.JasPdfDb;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Data.SqlClient;
using Microsoft.EntityFrameworkCore;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "PTG - jas,PTG - vo")]
    public class UserStandsModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly JasDbContext _jasDb;
        private readonly JasPdfDbContext _pdfDb;
        private readonly UserManager<JasUser> _userManager;

        public UserStandsModel(JasMtzDbContext context, JasDbContext jasDb, JasPdfDbContext pdfDb, UserManager<JasUser> userManager)
        {
            _context = context;
            _jasDb = jasDb;
            _pdfDb = pdfDb;
            _userManager = userManager;
        }

        public record PriceTagInfo(string? Name, int? Price, string? Unit, string? Size);

        public List<PtgUserStand> Stands { get; set; } = new();

        // Slovník reg_number → name pro zobrazení názvů v tabulce
        public Dictionary<string, string> RegNames { get; set; } = new(StringComparer.OrdinalIgnoreCase);

        // Slovník reg_number → cenový záznam z pdf_price_tag
        public Dictionary<string, PriceTagInfo> PriceItems { get; set; } = new(StringComparer.OrdinalIgnoreCase);

        // true = uživatel má IČO 27792803 → zobrazí se price_jas
        public bool IsJasUser { get; set; }

        [BindProperty]
        public string? NewStandName { get; set; }

        [BindProperty]
        public int EditStandId { get; set; }

        [BindProperty]
        public string? EditStandName { get; set; }

        [BindProperty]
        public int AddItemStandId { get; set; }

        // Reg. čísla z autocomplete (multiple select → model binder sbírá jako List)
        [BindProperty]
        public List<string> AddItemRegNumbers { get; set; } = new();

        public async Task<IActionResult> OnGetAsync(CancellationToken ct)
        {
            await LoadStandsAsync(ct);
            return Page();
        }

        // Přidat nový stojan
        public async Task<IActionResult> OnPostAddStandAsync(CancellationToken ct)
        {
            if (string.IsNullOrWhiteSpace(NewStandName))
            {
                TempData["Error"] = "Název stojanu nesmí být prázdný.";
                return RedirectToPage();
            }

            var userId = await GetUserIdAsync();
            _context.PtgUserStands.Add(new PtgUserStand
            {
                UserId = userId,
                Name = NewStandName.Trim(),
                CreatedAt = DateTime.UtcNow,
                UpdatedAt = DateTime.UtcNow,
            });
            await _context.SaveChangesAsync(ct);
            TempData["Success"] = $"Stojan \"{NewStandName.Trim()}\" byl vytvořen.";
            return RedirectToPage();
        }

        // Přejmenovat stojan
        public async Task<IActionResult> OnPostRenameStandAsync(CancellationToken ct)
        {
            if (string.IsNullOrWhiteSpace(EditStandName))
                return RedirectToPage();

            var userId = await GetUserIdAsync();
            var stand = await _context.PtgUserStands
                .FirstOrDefaultAsync(s => s.Id == EditStandId && s.UserId == userId, ct);
            if (stand is null) return RedirectToPage();

            stand.Name = EditStandName.Trim();
            stand.UpdatedAt = DateTime.UtcNow;
            await _context.SaveChangesAsync(ct);
            TempData["Success"] = "Stojan byl přejmenován.";
            return RedirectToPage();
        }

        // Smazat stojan
        public async Task<IActionResult> OnPostDeleteStandAsync(int standId, CancellationToken ct)
        {
            var userId = await GetUserIdAsync();
            var stand = await _context.PtgUserStands
                .FirstOrDefaultAsync(s => s.Id == standId && s.UserId == userId, ct);
            if (stand is not null)
            {
                _context.PtgUserStands.Remove(stand);
                await _context.SaveChangesAsync(ct);
                TempData["Success"] = $"Stojan \"{stand.Name}\" byl smazán.";
            }
            return RedirectToPage();
        }

        // Přidat jedno nebo více reg. čísel z autocomplete
        public async Task<IActionResult> OnPostAddItemAsync(CancellationToken ct)
        {
            var regs = AddItemRegNumbers
                .Select(r => r.Trim())
                .Where(r => !string.IsNullOrWhiteSpace(r))
                .Distinct(StringComparer.OrdinalIgnoreCase)
                .ToList();

            if (regs.Count == 0)
            {
                TempData["Error"] = "Žádné reg. číslo nebylo vybráno.";
                return RedirectToPage();
            }

            var userId = await GetUserIdAsync();
            var stand = await _context.PtgUserStands
                .Include(s => s.PtgUserStandItems)
                .FirstOrDefaultAsync(s => s.Id == AddItemStandId && s.UserId == userId, ct);
            if (stand is null) return RedirectToPage();

            int nextOrder = stand.PtgUserStandItems.Any()
                ? stand.PtgUserStandItems.Max(i => i.SortOrder) + 1
                : 0;

            var added = new List<string>();
            var skipped = new List<string>();

            foreach (var reg in regs)
            {
                bool exists = stand.PtgUserStandItems
                    .Any(i => string.Equals(i.RegNumber, reg, StringComparison.OrdinalIgnoreCase));

                if (exists)
                {
                    skipped.Add(reg);
                    continue;
                }

                stand.PtgUserStandItems.Add(new PtgUserStandItem
                {
                    IdStand = stand.Id,
                    RegNumber = reg,
                    SortOrder = nextOrder++
                });
                added.Add(reg);
            }

            if (added.Count > 0)
            {
                stand.UpdatedAt = DateTime.UtcNow;
                await _context.SaveChangesAsync(ct);

                // Jedno volání SP pro všechna právě přidaná reg. čísla
                var regList = string.Join(",", added);
                await _pdfDb.Database.ExecuteSqlRawAsync(
                    "EXEC dbo.sp_update_price_tag @reg_numbers",
                    new SqlParameter("@reg_numbers", regList));

                TempData["Success"] = added.Count == 1
                    ? $"Přidáno: {added[0]}"
                    : $"Přidáno {added.Count} položek: {string.Join(", ", added)}";
            }

            if (skipped.Count > 0)
                TempData["Error"] = $"Již v stojanu: {string.Join(", ", skipped)}";

            return RedirectToPage();
        }

        // Odebrat položku ze stojanu
        public async Task<IActionResult> OnPostDeleteItemAsync(int itemId, CancellationToken ct)
        {
            var userId = await GetUserIdAsync();
            var item = await _context.PtgUserStandItems
                .Include(i => i.IdStandNavigation)
                .FirstOrDefaultAsync(i => i.Id == itemId && i.IdStandNavigation.UserId == userId, ct);
            if (item is not null)
            {
                _context.PtgUserStandItems.Remove(item);
                await _context.SaveChangesAsync(ct);
            }
            return RedirectToPage();
        }

        private async Task LoadStandsAsync(CancellationToken ct)
        {
            var user = await _userManager.GetUserAsync(User);
            var userId = user?.Id ?? string.Empty;
            IsJasUser = string.Equals(user?.Ico, "27792803", StringComparison.Ordinal);

            Stands = await _context.PtgUserStands
                .Where(s => s.UserId == userId)
                .Include(s => s.PtgUserStandItems)
                .OrderBy(s => s.Name)
                .ToListAsync(ct);

            // Načti názvy a ceny pro všechna reg. čísla ze stojanů
            var allRegs = Stands
                .SelectMany(s => s.PtgUserStandItems)
                .Select(i => i.RegNumber)
                .Distinct(StringComparer.OrdinalIgnoreCase)
                .ToList();

            if (allRegs.Any())
            {
                RegNames = await _jasDb.ViPtgRegNumbers
                    .Where(r => allRegs.Contains(r.RegNumber))
                    .ToDictionaryAsync(
                        r => r.RegNumber,
                        r => r.Name ?? string.Empty,
                        StringComparer.OrdinalIgnoreCase,
                        ct);

                PriceItems = await _pdfDb.PdfPriceTags
                    .Where(p => allRegs.Contains(p.RegNumber))
                    .ToDictionaryAsync(
                        p => p.RegNumber,
                        p => new PriceTagInfo(p.Name, IsJasUser ? p.PriceJas : p.PriceNn, p.Unit, p.Size),
                        StringComparer.OrdinalIgnoreCase,
                        ct);
            }
        }

        private async Task<string> GetUserIdAsync()
        {
            var user = await _userManager.GetUserAsync(User);
            return user?.Id ?? string.Empty;
        }
    }
}
