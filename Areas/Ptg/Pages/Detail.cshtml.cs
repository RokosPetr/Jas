using Jas.Data.JasMtzDb;
using Jas.Helpers;
using Jas.Models.Ptg;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Data.SqlClient;
using Microsoft.EntityFrameworkCore;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "Administrator,PTG - admin,PTG - uživatel")]
    public class DetailModel : PageModel
    {
        private readonly JasMtzDbContext _context;

        public DetailModel(JasMtzDbContext context)
        {
            _context = context;
        }

        public StandCompany? Stand { get; set; }
        public List<Plate> Plates { get; set; } = new();
        public List<PlateItem> PlateItems { get; set; } = new();

        public async Task<IActionResult> OnGetAsync(int id)
        {
            using var conn = _context.Database.GetDbConnection();
            await conn.OpenAsync();

            using var cmd = conn.CreateCommand();
            cmd.CommandText = "EXEC sp_ptg_GetStandDetail @IdStand";
            cmd.CommandType = System.Data.CommandType.Text;

            var idParam = new SqlParameter("@IdStand", id);
            cmd.Parameters.Add(idParam);

            using var reader = await cmd.ExecuteReaderAsync();

            var stands = _context.Translate<StandCompany>(reader);
            Stand = stands.FirstOrDefault();

            await reader.NextResultAsync();
            Plates = _context.Translate<Plate>(reader).ToList();

            await reader.NextResultAsync();
            PlateItems = _context.Translate<PlateItem>(reader).ToList();

            return Page();
        }
    }
}
