using AutoMapper;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
using Jas.Infrastructure.Ptg;
using Jas.Models.Ptg;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Data.SqlClient;
using Microsoft.EntityFrameworkCore;
using System.Linq;
using System.Text.RegularExpressions;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    //[Authorize]
    [Authorize(Roles = "PTG - admin,PTG - user")]
    //[Authorize(Roles = "PTG - admin")]
    public class IndexModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly UserManager<JasUser> _userManager;
        private readonly IStandSearchReader _searchReader;

        [BindProperty(SupportsGet = true)]
        public string? ProducerName { get; set; }
        [BindProperty(SupportsGet = true)]
        public string? SearchString { get; set; }
        [BindProperty(SupportsGet = true)]
        public string? Available { get; set; }
        public List<StandCompany> Stands { get; set; }
        public List<JasProducer> Producers { get; set; }
        [BindProperty(SupportsGet = true)]
        public string? SearchType { get; set; }
        [BindProperty(SupportsGet = true)]
        public List<int> StandTypes { get; set; } = new() { 1, 2, 3 };
        
        public IndexModel(JasMtzDbContext context, IMapper mapper, UserManager<JasUser> userManager, IStandSearchReader searchReader)
        {
            _context = context;
            _mapper = mapper;
            _userManager = userManager;
            _searchReader = searchReader;
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
            SqlParameter searchTypeParam = new SqlParameter("@SearchType", SearchType ?? (object)DBNull.Value);

            SqlParameter ownOrAvailable = new SqlParameter("@OwnOrAvailable", System.Data.SqlDbType.Bit)
            {
                Value = string.Equals(Available, "portfolio", StringComparison.OrdinalIgnoreCase) ? 0 : 1
            };

            // pokud nic nepøišlo v query, necháme default 1,2,3
            if (StandTypes == null || StandTypes.Count == 0)
            {
                StandTypes = new List<int> { 1, 2, 3 };
            }

            // øetìzec "1,2,3" pro SQL
            var standTypesString = string.Join(',', StandTypes);

            SqlParameter standTypesParam = new SqlParameter("@StandTypes", System.Data.SqlDbType.NVarChar, 50)
            {
                Value = (object)standTypesString ?? DBNull.Value
            };

            Stands = await _context.StandCompany
                .FromSqlRaw(
                    "EXEC sp_ptg_GetStandCompanies @Ico, @ProducerName, @SearchString, @Voj, @OwnOrAvailable, @SearchType, @StandTypes",
                    icoParam, producerNameParam, searchStringParam, vojParam, ownOrAvailable, searchTypeParam, standTypesParam)
                .ToListAsync();

            List<int?> producerIds = Stands.Select(s => s.IdMkProducer).Distinct().ToList();
            Producers = await _context.JasProducers.Where(w => producerIds.Contains(w.MkId)).GroupBy(p => p.MkId).Select(g => g.First()).ToListAsync();
            if (producerIds.Contains(null))
            {
                Producers.Add(new JasProducer() { Name = "MIX" });
            }

            return Page();
        }

        public async Task<JsonResult> OnGetSearch(string q, bool onlyMine)
        {
            if (string.IsNullOrWhiteSpace(q) || q.Length < 3)
                return new JsonResult(Array.Empty<object>());

            string? userId = _userManager.GetUserId(User); // Získá string ID
            JasUser? user = await _userManager.FindByIdAsync(userId!);

            var results = await _searchReader.SearchAsync(q, user.Ico, user.Mop9Voj, onlyMine);

            // autocomplete oèekává label + value

            var size = results.FirstOrDefault(w => w.Type.Equals("size"));

            var mappedSize = size == null
                ? null
                : new
                {
                    label = size.Label,
                    value = size.Value,
                    type = size.Type,
                    weight = size.Weight + 1000,
                    idStand = (int?)null,
                    idPlate = (int?)null,
                    plateCount = (int?)null,
                    pieceStand = (int?)null,
                    url = Url.Page("Index", new { area = "Ptg", SearchString = size.Value, SearchType = size.Type, Available = onlyMine ? "expozice" : "portfolio" })
                };

            var mappedOther = results.Where(w => !w.Type.Equals("size")).Select(r => new
            {
                label = r.Label,
                value = r.Value,
                type = r.Type,
                weight = r.Weight,
                idStand = r.IdStand,
                idPlate = r.IdPlate,
                plateCount = r.PlateCount,
                pieceStand = r.PieceStand,
                url = r.IdStand.HasValue
                    ? (r.PieceStand.HasValue && (bool)r.PieceStand)
                        ? Url.Page("PieceDetail", new { area = "Ptg", id = r.IdStand })
                        : Url.Page("PlateDetail", new { area = "Ptg", id = r.IdStand, idPlate = r.IdPlate })
                    : Url.Page("Index", new { area = "Ptg", SearchString = r.Value, SearchType = r.Type, Available = onlyMine ? "expozice" : "portfolio" })
            });

            var mapped =
                mappedOther
                    .Concat(mappedSize != null ? new[] { mappedSize } : Array.Empty<object>())
                    .OrderByDescending(x => (int)x.GetType().GetProperty("weight")!.GetValue(x)!)
                    .Take(15)
                    .ToList();

            return new JsonResult(mapped);
        }

    }
}
