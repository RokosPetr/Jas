using Jas.Application.Abstractions; // ⬅ IImageStore
using Jas.Data.JasMtzDb;
using Jas.Helpers;
using Jas.Models.Ptg;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Data.SqlClient;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Caching.Memory;
using AutoMapper;
using System.Data;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "Administrator,PTG - admin,PTG - uživatel")]
    public class PlateDetailModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMemoryCache _cache;
        private readonly IImageStore _imageStore;   // ⬅ injektované úložiště obrázků
        private readonly IMapper _mapper;

        public PlateDetailModel(JasMtzDbContext context, IMemoryCache cache, IImageStore imageStore, IMapper mapper)
        {
            _context = context;
            _cache = cache;
            _imageStore = imageStore;
            _mapper = mapper;
        }

        public StandCompany? Stand { get; set; }
        public List<Plate> Plates { get; set; } = new();
        public List<PlateItem> PlateItems { get; set; } = new();
        public Plate? CurrentPlate { get; set; }

        // vyhneme se kolizi s "page"
        [BindProperty(SupportsGet = true)]
        public int Plate { get; set; } = 1;

        public async Task<IActionResult> OnGetAsync(int id)
        {
            await LoadAllAsync(id);

            if (Plate < 1) Plate = 1;
            if (Plate > Plates.Count) Plate = Plates.Count;
            CurrentPlate = Plates[Plate - 1];

            return Page();
        }

        // AJAX: vrací jen partial s aktuálním platem (obrázek + tabulka)
        public async Task<IActionResult> OnGetPlateAsync(int id, int plate = 1)
        {
            await LoadAllAsync(id);

            if (plate < 1) plate = 1;
            if (plate > Plates.Count) plate = Plates.Count;

            var current = Plates[plate - 1];

            var vm = new PlateContentVm
            {
                StandId = Stand!.IdStand,
                PlateIndex = plate,
                TotalPlates = Plates.Count,
                ImgUrl = string.IsNullOrEmpty(current.ImgUrl) ? "/images/no-picture.png" : current.ImgUrl,
                Items = PlateItems
                    .Where(i => i.Id_Pt_Plate == current.Id)
                    .OrderBy(i => i.Item_Order)
                    .ToList()
            };

            // ⬇️ Tady přepočítáme HasImage „na tvrdo“ jen pro aktuální plát
            var ct = HttpContext.RequestAborted;
            await EnsureHasImageForCurrentPlateAsync(vm.Items, ct);

            return Partial("_PlateContent", vm);
        }

        private async Task LoadAllAsync(int idStand)
        {
            var cacheKey = $"stand:{idStand}:detail";
            if (!_cache.TryGetValue(cacheKey, out StandDetailCache? cached))
            {
                await using var conn = (SqlConnection)_context.Database.GetDbConnection();
                await conn.OpenAsync();

                await using var cmd = new SqlCommand(@"EXEC dbo.sp_ptg_GetStandDetail @IdStand = @id;", conn);
                cmd.Parameters.AddWithValue("@id", idStand);

                await using var reader = await cmd.ExecuteReaderAsync();

                // 1) stand
                var stand = _mapper
                    .Map<IDataReader, IEnumerable<StandCompany>>(reader)
                    .FirstOrDefault() ?? throw new InvalidOperationException("Stand not found");

                // 2) plates
                await reader.NextResultAsync();
                var plates = _mapper
                    .Map<IDataReader, IEnumerable<Plate>>(reader)
                    .ToList();

                // 3) items
                await reader.NextResultAsync();
                var items = _mapper
                    .Map<IDataReader, IEnumerable<PlateItem>>(reader)
                    .ToList();

                // rychlý flag – reálnou dostupnost řeší EnsureHasImagesAsync(...)
                foreach (var it in items)
                    it.HasImage = !string.IsNullOrWhiteSpace(it.ImgUrl);

                cached = new StandDetailCache
                {
                    Stand = stand,
                    Plates = plates,
                    Items = items
                };

                var opts = new MemoryCacheEntryOptions()
                    .SetAbsoluteExpiration(TimeSpan.FromMinutes(10))
                    .SetSlidingExpiration(TimeSpan.FromMinutes(3));

                _cache.Set(cacheKey, cached, opts);
            }

            Stand = cached!.Stand;
            Plates = cached.Plates;
            PlateItems = cached.Items;
        }
        
        //private async Task LoadAllAsync(int idStand)
        //{
        //    var cacheKey = $"stand:{idStand}:detail";
        //    if (!_cache.TryGetValue(cacheKey, out StandDetailCache? cached))
        //    {
        //        using var conn = (SqlConnection)_context.Database.GetDbConnection();
        //        await conn.OpenAsync();
        //        using var cmd = new SqlCommand(@"
        //            EXEC dbo.sp_ptg_GetStandDetail @IdStand = @id;
        //        ", conn);
        //        cmd.Parameters.AddWithValue("@id", idStand);

        //        using var reader = await cmd.ExecuteReaderAsync();

        //        var stand = _context.Translate<StandCompany>(reader).FirstOrDefault()
        //                   ?? throw new InvalidOperationException("Stand not found");

        //        await reader.NextResultAsync();
        //        var plates = _context.Translate<Plate>(reader).ToList();

        //        await reader.NextResultAsync();
        //        var items = _context.Translate<PlateItem>(reader).ToList();

        //        foreach (var it in items)
        //            it.HasImage = !string.IsNullOrWhiteSpace(it.ImgUrl);

        //        cached = new StandDetailCache
        //        {
        //            Stand = stand,
        //            Plates = plates,
        //            Items = items
        //        };

        //        var opts = new MemoryCacheEntryOptions()
        //            .SetAbsoluteExpiration(TimeSpan.FromMinutes(10))
        //            .SetSlidingExpiration(TimeSpan.FromMinutes(3));

        //        _cache.Set(cacheKey, cached, opts);
        //    }

        //    Stand = cached!.Stand;
        //    Plates = cached.Plates;
        //    PlateItems = cached.Items;
        //}

        /// <summary>
        /// Přesně ověří dostupnost pro položky aktuálního plátu:
        /// - prázdná URL → HasImage = false
        /// - pokud lokál neexistuje, zkusí stáhnout; při 404/403 apod. → HasImage = false
        /// </summary>
        private async Task EnsureHasImageForCurrentPlateAsync(List<PlateItem> items, CancellationToken ct)
        {
            // Mírné omezení souběhu, ať to nedělá špičky (stačí 4–6)
            var sem = new SemaphoreSlim(4);
            var tasks = items.Select(async it =>
            {
                if (string.IsNullOrWhiteSpace(it.ImgUrl))
                {
                    it.HasImage = false;
                    return;
                }

                await sem.WaitAsync(ct);
                try
                {
                    // IImageStore umí absolutní URL i "host/path?query"
                    it.HasImage = await _imageStore.TryEnsureLocalAsync(it.ImgUrl!, ct);
                }
                catch
                {
                    it.HasImage = false;
                }
                finally
                {
                    sem.Release();
                }
            });

            await Task.WhenAll(tasks);
        }

        // ---------- Cache container ----------
        private sealed class StandDetailCache
        {
            public StandCompany? Stand { get; init; }
            public List<Plate> Plates { get; init; } = new();
            public List<PlateItem> Items { get; init; } = new();
        }
    }

    // ---------- ViewModel pro partial ----------
    public class PlateContentVm
    {
        public int StandId { get; set; }
        public int PlateIndex { get; set; }
        public int TotalPlates { get; set; }
        public string? ImgUrl { get; set; }
        public List<PlateItem> Items { get; set; } = new();
    }
}
