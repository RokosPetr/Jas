using Jas.Application.Abstractions; // IImageStore
using Jas.Data.JasMtzDb;
using Jas.Helpers;
using Jas.Models.Ptg;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Data.SqlClient;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Caching.Memory;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "Administrator,PTG - admin,PTG - uživatel")]
    public class PieceDetailModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMemoryCache _cache;
        private readonly IImageStore _imageStore;

        public PieceDetailModel(JasMtzDbContext context, IMemoryCache cache, IImageStore imageStore)
        {
            _context = context;
            _cache = cache;
            _imageStore = imageStore;
        }

        public StandCompany? Stand { get; set; }
        public List<Plate> Plates { get; set; } = new();
        public List<PlateItem> PlateItems { get; set; } = new();

        public async Task<IActionResult> OnGetAsync(int id, CancellationToken ct)
        {
            await LoadAllAsync(id);

            var firstPlate = Plates.FirstOrDefault();
            if (firstPlate is null)
                return Page();

            await EnsureHasImagesAsync(PlateItems.Where(i => i.Id_Pt_Plate == firstPlate.Id), ct);
            return Page();
        }

        private async Task EnsureHasImagesAsync(IEnumerable<PlateItem> items, CancellationToken ct)
        {
            using var sem = new SemaphoreSlim(6);
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
            }).ToList();

            await Task.WhenAll(tasks);
        }

        private async Task LoadAllAsync(int idStand)
        {
            var cacheKey = $"stand:{idStand}:detail";
            if (!_cache.TryGetValue(cacheKey, out StandDetailCache? cached))
            {
                using var conn = (SqlConnection)_context.Database.GetDbConnection();
                await conn.OpenAsync();
                using var cmd = new SqlCommand(@"
                    EXEC dbo.sp_ptg_GetStandDetail @IdStand = @id;
                ", conn);
                cmd.Parameters.AddWithValue("@id", idStand);

                using var reader = await cmd.ExecuteReaderAsync();

                var stand = _context.Translate<StandCompany>(reader).FirstOrDefault()
                           ?? throw new InvalidOperationException("Stand not found");

                await reader.NextResultAsync();
                var plates = _context.Translate<Plate>(reader).ToList();

                await reader.NextResultAsync();
                var items = _context.Translate<PlateItem>(reader).ToList();

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

        private sealed class StandDetailCache
        {
            public StandCompany? Stand { get; init; }
            public List<Plate> Plates { get; init; } = new();
            public List<PlateItem> Items { get; init; } = new();
        }
    }
}