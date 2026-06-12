using System.Threading.Tasks;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;

namespace Jas.Areas.Srv.Pages
{
    [Area("Srv")]
    [Authorize(Roles = "SRV - admin,SRV - user")]
    public class DetailPrintModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly UserManager<JasUser> _userManager;

        public DetailPrintModel(JasMtzDbContext context, UserManager<JasUser> userManager)
        {
            _context = context;
            _userManager = userManager;
        }

        public SrvMaintenanceRequest RequestItem { get; set; } = null!;
        public string DepartmentName { get; set; } = string.Empty;
        public string UserName { get; set; } = string.Empty;
        public Dictionary<string, string> NoteUserNames { get; set; } = new();

        public async Task<IActionResult> OnGetAsync(int id)
        {
            var item = await _context.SrvMaintenanceRequests
                .Include(m => m.SrvMaintenanceRequestNotes)
                .AsNoTracking()
                .FirstOrDefaultAsync(m => m.Id == id);

            if (item == null)
            {
                return NotFound();
            }

            // Seřadíme poznámky (komentáře) podle data od nejstarší
            item.SrvMaintenanceRequestNotes = item.SrvMaintenanceRequestNotes
                .OrderBy(n => n.CreatedAt)
                .ToList();

            RequestItem = item;

            // Načtení názvu střediska
            if (item.IdDepartment != 0)
            {
                var dept = await _context.JasDepartments.FirstOrDefaultAsync(d => d.Id == item.IdDepartment);
                DepartmentName = dept?.Name ?? string.Empty;
            }

            // Načtení jména uživatele
            if (!string.IsNullOrEmpty(item.IdUser))
            {
                var user = await _userManager.FindByIdAsync(item.IdUser);
                if (user != null)
                {
                    UserName = string.IsNullOrWhiteSpace(user.Name) ? user.UserName : user.Name;
                }
            }

            // Načtení jmen uživatelů pro historii (poznámky)
            var noteUserIds = item.SrvMaintenanceRequestNotes
                .Where(n => !string.IsNullOrEmpty(n.IdUser))
                .Select(n => n.IdUser!)
                .Distinct()
                .ToList();

            foreach (var uId in noteUserIds)
            {
                var u = await _userManager.FindByIdAsync(uId);
                if (u != null)
                {
                    NoteUserNames[uId] = string.IsNullOrWhiteSpace(u.Name) ? u.UserName : u.Name;
                }
                else
                {
                    NoteUserNames[uId] = "Neznámý uživatel";
                }
            }

            return Page();
        }

        // Zde můžete doplnit i stejné pomocné funkce pro formátování kategorie a data jako v IndexModel
    }
}