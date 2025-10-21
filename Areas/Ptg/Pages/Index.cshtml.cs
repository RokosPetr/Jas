using AutoMapper;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
using Jas.Models.Ptg;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Data.SqlClient;
using Microsoft.EntityFrameworkCore;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "Administrator,PTG - admin,PTG - uživatel")]
    public class IndexModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly UserManager<JasUser> _userManager;

        [BindProperty(SupportsGet = true)]
        public string? ProducerName { get; set; }
        [BindProperty(SupportsGet = true)]
        public string? SearchString { get; set; }
        [BindProperty(SupportsGet = true)]
        public string? Available { get; set; }
        public List<StandCompany> Stands { get; set; }
        public List<JasProducer> Producers { get; set; }

        public IndexModel(JasMtzDbContext context, IMapper mapper, UserManager<JasUser> userManager)
        {
            _context = context;
            _mapper = mapper;
            _userManager = userManager;
        }

        public async Task<IActionResult> OnGetAsync(string? ProducerName, string? SearchString)
        {
            if (string.IsNullOrEmpty(Available))
            {
                return RedirectToPage(null, new { Available = "expozice", ProducerName });
            }

            string? userId = _userManager.GetUserId(User); // Získá string ID
            JasUser? user = await _userManager.FindByIdAsync(userId!);

            if (user == null) return Page();

            SqlParameter icoParam = new SqlParameter("@Ico", user.Ico ?? (object)DBNull.Value);
            SqlParameter producerNameParam = new SqlParameter("@ProducerName", ProducerName ?? (object)DBNull.Value);
            SqlParameter searchStringParam = new SqlParameter("@SearchString", SearchString ?? (object)DBNull.Value);
            SqlParameter vojParam = new SqlParameter("@Voj", user.Mop9Voj ?? (object)DBNull.Value);
            
            SqlParameter ownOrAvailable = new SqlParameter("@OwnOrAvailable", System.Data.SqlDbType.Bit)
            {
                Value = string.Equals(Available, "portfolio", StringComparison.OrdinalIgnoreCase) ? 1 : 0
            };

            Stands = await _context.StandCompany
                .FromSqlRaw("EXEC sp_ptg_GetStandCompanies @Ico, @ProducerName, @SearchString, @Voj, @OwnOrAvailable",
                    icoParam, producerNameParam, searchStringParam, vojParam, ownOrAvailable)
                .ToListAsync();

            List<int?> producerIds = Stands.Select(s => s.IdMkProducer).Distinct().ToList();
            Producers = await _context.JasProducers.Where(w => producerIds.Contains(w.MkId)).ToListAsync();
            if (producerIds.Contains(null))
            {
                Producers.Add(new JasProducer() { Name = "MIX" });
            }

            return Page();
        }

    }
}
