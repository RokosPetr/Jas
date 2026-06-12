using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using Jas.Data.JasDb;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Caching.Memory;
using AutoMapper;

namespace Jas.Areas.Intranet.Pages
{
    [Area("Intranet")]
    public class GnRewardsModel : PageModel
    {
        private readonly JasDbContext _context;
        private readonly IMemoryCache _cache;
        private readonly IMapper _mapper;

        public GnRewardsModel(JasDbContext context, IMemoryCache cache, IMapper mapper)
        {
            _context = context;
            _cache = cache;
            _mapper = mapper;
        }

        public IList<Jas.Models.Jas.GnReward> Rewards { get; set; } = new List<Jas.Models.Jas.GnReward>();

        // Proměnné pro naplnění slicerů (filtrů) pro frontend počáteční renderování
        public List<string> AllPersons { get; set; } = new();
        public List<int> AllYears { get; set; } = new();
        public List<string> AllMonths { get; set; } = new();
        public List<string> AllStores { get; set; } = new();

        public async Task OnGetAsync()
        {
            // Slicery mohou být nacachovány, padají z obrovského SQL dotazu
            if (!_cache.TryGetValue("GnRewards_AllPersons", out List<string> cachedPersons))
            {
                cachedPersons = await _context.GnRewards.Where(x => !string.IsNullOrEmpty(x.PersonName)).Select(x => x.PersonName.Trim()).Distinct().OrderBy(x => x).ToListAsync();
                _cache.Set("GnRewards_AllPersons", cachedPersons, TimeSpan.FromHours(12));
            }
            AllPersons = cachedPersons;

            if (!_cache.TryGetValue("GnRewards_AllYears", out List<int> cachedYears))
            {
                cachedYears = await _context.GnRewards.Select(x => x.Year).Distinct().OrderByDescending(x => x).ToListAsync();
                _cache.Set("GnRewards_AllYears", cachedYears, TimeSpan.FromHours(12));
            }
            AllYears = cachedYears;
            
            if (!_cache.TryGetValue("GnRewards_AllMonths", out List<string> cachedMonths))
            {
                var monthNamesOrder = new List<string> { "leden", "únor", "březen", "duben", "květen", "červen", "červenec", "srpen", "září", "říjen", "listopad", "prosinec" };
                var rawMonths = await _context.GnRewards.Select(x => x.MonthName.Trim().ToLower()).Distinct().ToListAsync();
                cachedMonths = rawMonths.OrderBy(x => monthNamesOrder.IndexOf(x) >= 0 ? monthNamesOrder.IndexOf(x) : 99).ToList();
                _cache.Set("GnRewards_AllMonths", cachedMonths, TimeSpan.FromHours(12));
            }
            AllMonths = cachedMonths;

            if (!_cache.TryGetValue("GnRewards_AllStores", out List<string> cachedStores))
            {
                // Řazení prodejen provádíme primárně dle sloupce Store (číselný/jiný primární identifikátor), abychom zachovali strukturu,
                // ale zobrazovat ho všude budeme pořád jen pod textovým popiskem StoreName.
                // U distinct nad zataženým stringem ztrácíme ID pořadí, takže musíme vybrat páry a dělat GroupBy (LINQ in-memory po selectu pro zamezení problémů překladatele s distinct na EF).
                var rawStores = await _context.GnRewards
                    .Where(x => !string.IsNullOrEmpty(x.StoreName))
                    .Select(x => new { Id = x.Store, Name = x.StoreName.Trim() })
                    .ToListAsync();

                cachedStores = rawStores
                    .GroupBy(x => x.Name)
                    .Select(g => new { Name = g.Key, Id = g.Min(x => x.Id) })
                    .OrderBy(x => x.Id)
                    .Select(x => x.Name)
                    .ToList();

                _cache.Set("GnRewards_AllStores", cachedStores, TimeSpan.FromHours(12));
            }
            AllStores = cachedStores;
        }

        public async Task<IActionResult> OnGetPivotDataAsync([FromQuery] string[] selectedPersons = null, [FromQuery] int[] selectedYears = null, [FromQuery] string[] selectedMonths = null, [FromQuery] string[] selectedStores = null)
        {
            // Pokud filtrujeme zbackendu a nemáme na DB indexy pro všechny kombinace, můžeme nacachovat základní sadu dat a filtrovat v paměti webserveru.
            if (!_cache.TryGetValue("GnRewards_FullData", out List<Jas.Models.Jas.GnReward> data))
            {
                var dbData = await _context.GnRewards.AsNoTracking().ToListAsync();

                // Přeměníme záznamy na paměťový List pomocí namapování z AutoMapper (nakofigurovano v MappingProfile)
                data = _mapper.Map<List<Jas.Models.Jas.GnReward>>(dbData);
                
                // Cache si drží paměť do restartu poolu nebo po zadanou dobu.
                // Vzhledem k importům můžeme dát expiraci na X hodin, nebo použít absolutní expiraci vždy na půlnoc, případně můžete z importního jobu volat cache.Remove("GnRewards_FullData").
                _cache.Set("GnRewards_FullData", data, TimeSpan.FromHours(1)); 
            }

            // Uložení stavů před filtry pro správné zobrazení dostupných slicerů pro samotnou aktuální skupinu (aby si slicery neblokovaly samy možnost výběru dalších)
            var availableDataForStores = data.ToList();
            if (selectedPersons != null && selectedPersons.Length > 0) availableDataForStores = availableDataForStores.Where(x => selectedPersons.Contains(x.PersonName)).ToList();
            if (selectedYears != null && selectedYears.Length > 0) availableDataForStores = availableDataForStores.Where(x => selectedYears.Contains(x.Year)).ToList();
            if (selectedMonths != null && selectedMonths.Length > 0) { var lowerMonths = selectedMonths.Select(m => m.ToLower()).ToList(); availableDataForStores = availableDataForStores.Where(x => lowerMonths.Contains(x.MonthName.ToLower())).ToList(); }

            var availableDataForPersons = data.ToList();
            if (selectedStores != null && selectedStores.Length > 0) availableDataForPersons = availableDataForPersons.Where(x => selectedStores.Contains(x.StoreName)).ToList();
            if (selectedYears != null && selectedYears.Length > 0) availableDataForPersons = availableDataForPersons.Where(x => selectedYears.Contains(x.Year)).ToList();
            if (selectedMonths != null && selectedMonths.Length > 0) { var lowerMonths = selectedMonths.Select(m => m.ToLower()).ToList(); availableDataForPersons = availableDataForPersons.Where(x => lowerMonths.Contains(x.MonthName.ToLower())).ToList(); }

            var availableDataForYears = data.ToList();
            if (selectedStores != null && selectedStores.Length > 0) availableDataForYears = availableDataForYears.Where(x => selectedStores.Contains(x.StoreName)).ToList();
            if (selectedPersons != null && selectedPersons.Length > 0) availableDataForYears = availableDataForYears.Where(x => selectedPersons.Contains(x.PersonName)).ToList();
            if (selectedMonths != null && selectedMonths.Length > 0) { var lowerMonths = selectedMonths.Select(m => m.ToLower()).ToList(); availableDataForYears = availableDataForYears.Where(x => lowerMonths.Contains(x.MonthName.ToLower())).ToList(); }

            var availableDataForMonths = data.ToList();
            if (selectedStores != null && selectedStores.Length > 0) availableDataForMonths = availableDataForMonths.Where(x => selectedStores.Contains(x.StoreName)).ToList();
            if (selectedPersons != null && selectedPersons.Length > 0) availableDataForMonths = availableDataForMonths.Where(x => selectedPersons.Contains(x.PersonName)).ToList();
            if (selectedYears != null && selectedYears.Length > 0) availableDataForMonths = availableDataForMonths.Where(x => selectedYears.Contains(x.Year)).ToList();

            // Aplikace filtrů ("slicerů") pro samotná výsledná data tabulky
            if (selectedPersons != null && selectedPersons.Length > 0)
            {
                data = data.Where(x => selectedPersons.Contains(x.PersonName)).ToList();
            }
            if (selectedYears != null && selectedYears.Length > 0)
            {
                data = data.Where(x => selectedYears.Contains(x.Year)).ToList();
            }
            if (selectedMonths != null && selectedMonths.Length > 0)
            {
                var lowerMonths = selectedMonths.Select(m => m.ToLower()).ToList();
                data = data.Where(x => lowerMonths.Contains(x.MonthName.ToLower())).ToList();
            }
            if (selectedStores != null && selectedStores.Length > 0)
            {
                data = data.Where(x => selectedStores.Contains(x.StoreName)).ToList();
            }

            var pivotResult = new PivotResult();

            var storesToIterate = data
                .Where(x => x.StoreName != null && x.StoreName.Trim() != "")
                .GroupBy(x => new { Name = x.StoreName.Trim(), Id = x.Store }) // Seskupujeme podle obou atributů k udržení ID po celou dobu groupování
                .ToList();

            // Zde pro tabulku řadíme rovněž specificky podle Store (Id) místo podle textového názvu.
            var sortedStores = storesToIterate.OrderBy(x => x.Key.Id).ToList();

            foreach (var storeGroup in sortedStores)
            {
                var s = new PivotStore { Name = storeGroup.Key.Name };

                var personsToIterate = storeGroup
                    .Where(x => x.PersonName != null && x.PersonName.Trim() != "")
                    .GroupBy(x => x.PersonName.Trim())
                    .ToList();

                var sortedPersons = personsToIterate.OrderBy(x => x.Key).ToList();

                foreach (var personGroup in sortedPersons)
                {
                    var p = new PivotPerson { Name = personGroup.Key };

                    var groupedByYear = personGroup
                        .GroupBy(x => x.Year)
                        .OrderByDescending(x => x.Key);

                    foreach (var yearGroup in groupedByYear)
                    {
                        var y = new PivotYear { Year = yearGroup.Key };

                        var monthNamesOrder = new List<string> { "leden", "únor", "březen", "duben", "květen", "červen", "červenec", "srpen", "září", "říjen", "listopad", "prosinec" };

                        var groupedByMonth = yearGroup
                            .GroupBy(x => x.MonthName.Trim().ToLower())
                            .ToList() // Zajistíme přechod z IGrouping (IQueryable/IEnumerable varování) na čistý in-memory list než použijeme indexOf
                            .OrderBy(x => monthNamesOrder.IndexOf(x.Key) >= 0 ? monthNamesOrder.IndexOf(x.Key) : 99);

                        foreach (var monthGroup in groupedByMonth)
                        {
                            var m = new PivotMonth { Month = monthGroup.Key };

                            m.AutorGN = monthGroup.Where(r => r.RoleName == "Autor GN").Sum(r => r.RewardAmount);
                            m.Prodejce = monthGroup.Where(r => r.RoleName == "Prodejce").Sum(r => r.RewardAmount);
                            m.AutorCN = monthGroup.Where(r => r.RoleName == "Autor CN").Sum(r => r.RewardAmount);

                            y.Months.Add(m);
                        }

                        y.AutorGN = y.Months.Sum(m => m.AutorGN);
                        y.Prodejce = y.Months.Sum(m => m.Prodejce);
                        y.AutorCN = y.Months.Sum(m => m.AutorCN);

                        p.Years.Add(y);
                    }

                    p.AutorGN = p.Years.Sum(y => y.AutorGN);
                    p.Prodejce = p.Years.Sum(y => y.Prodejce);
                    p.AutorCN = p.Years.Sum(y => y.AutorCN);

                    s.Persons.Add(p);
                }

                s.AutorGN = s.Persons.Sum(p => p.AutorGN);
                s.Prodejce = s.Persons.Sum(p => p.Prodejce);
                s.AutorCN = s.Persons.Sum(p => p.AutorCN);

                pivotResult.Stores.Add(s);
            }

            pivotResult.GrandTotal.AutorGN = pivotResult.Stores.Sum(s => s.AutorGN);
            pivotResult.GrandTotal.Prodejce = pivotResult.Stores.Sum(s => s.Prodejce);
            pivotResult.GrandTotal.AutorCN = pivotResult.Stores.Sum(s => s.AutorCN);

            // Zajištění aktualizovaných slicerů - každá skupina počítá jen zbylé filtry mimo sebe samu, jinak by multi-select nebyl u daného sliceru možný
            pivotResult.AvailableStores = availableDataForStores
                .Where(x => !string.IsNullOrEmpty(x.StoreName))
                .GroupBy(x => x.StoreName.Trim())
                .Select(g => new { Name = g.Key, Id = g.Min(x => x.Store) })
                .OrderBy(x => x.Id)
                .Select(x => x.Name)
                .ToList();

            pivotResult.AvailablePersons = availableDataForPersons.Where(x => !string.IsNullOrEmpty(x.PersonName)).Select(x => x.PersonName.Trim()).Distinct().OrderBy(x => x).ToList();
            pivotResult.AvailableYears = availableDataForYears.Select(x => x.Year).Distinct().OrderByDescending(x => x).ToList();
            var monthNamesOrderFilter = new List<string> { "leden", "únor", "březen", "duben", "květen", "červen", "červenec", "srpen", "září", "říjen", "listopad", "prosinec" };
            
            var uniqueMonths = availableDataForMonths.Select(x => x.MonthName.Trim().ToLower()).Distinct().ToList();
            pivotResult.AvailableMonths = uniqueMonths.OrderBy(x => monthNamesOrderFilter.IndexOf(x) >= 0 ? monthNamesOrderFilter.IndexOf(x) : 99).ToList();

            return Partial("_GnRewardsPivotTable", pivotResult);
        }
    }

    public class PivotResult
    {
        public List<PivotStore> Stores { get; set; } = new();
        public PivotTotals GrandTotal { get; set; } = new();

        public List<string> AvailablePersons { get; set; } = new();
        public List<int> AvailableYears { get; set; } = new();
        public List<string> AvailableMonths { get; set; } = new();
        public List<string> AvailableStores { get; set; } = new();
    }

    public class PivotTotals
    {
        public decimal AutorGN { get; set; }
        public decimal Prodejce { get; set; }
        public decimal AutorCN { get; set; }
        public decimal Celkem => AutorGN + Prodejce + AutorCN;
    }

    public class PivotStore : PivotTotals
    {
        public string Name { get; set; } = default!;
        public List<PivotPerson> Persons { get; set; } = new();
    }

    public class PivotPerson : PivotTotals
    {
        public string Name { get; set; } = default!;
        public List<PivotYear> Years { get; set; } = new();
    }

    public class PivotYear : PivotTotals
    {
        public int Year { get; set; }
        public List<PivotMonth> Months { get; set; } = new();
    }

    public class PivotMonth : PivotTotals
    {
        public string Month { get; set; } = default!;
    }
}
