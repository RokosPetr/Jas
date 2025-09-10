using AutoMapper;
using Jas.Application.Abstractions; // ⬅ IImageStore
using Jas.Application.Abstractions.Ptg;
using Jas.Data.JasMtzDb;
using Jas.Helpers;
using Jas.Models.Ptg;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Data.SqlClient;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Caching.Memory;
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
        private readonly IStandDetailReader _standReader;

        public PlateDetailModel(JasMtzDbContext context, IMemoryCache cache, IImageStore imageStore, IMapper mapper, IStandDetailReader standReader)
        {
            _context = context;
            _cache = cache;
            _imageStore = imageStore;
            _mapper = mapper;
            _standReader = standReader;
        }

        public StandCompany? Stand { get; set; }
        public List<Plate> Plates { get; set; } = new();
        public List<PlateItem> PlateItems { get; set; } = new();
        public Plate? CurrentPlate { get; set; }

        // vyhneme se kolizi s "page"
        [BindProperty(SupportsGet = true)]
        public int Plate { get; set; } = 1;

        public async Task<IActionResult> OnGetAsync(int id, CancellationToken ct)
        {
            var data = await _standReader.GetAsync(id, ct);
            Stand = data.Stand;
            Plates = data.Plates;
            PlateItems = data.Items;

            if (Plate < 1) Plate = 1;
            if (Plate > Plates.Count) Plate = Plates.Count;
            CurrentPlate = Plates[Plate - 1];

            return Page();
        }

        // AJAX: vrací jen partial s aktuálním platem (obrázek + tabulka)
        public async Task<IActionResult> OnGetPlateAsync(int id, CancellationToken ct, int plate = 1)
        {
            var data = await _standReader.GetAsync(id, ct);
            Stand = data.Stand;
            Plates = data.Plates;
            PlateItems = data.Items;
            
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
            var requestCt = HttpContext.RequestAborted; // nebo použij rovnou 'cancellationToken'
            await EnsureHasImageForCurrentPlateAsync(vm.Items, requestCt);

            return Partial("_PlateContent", vm);
        }

        private async Task EnsureHasImageForCurrentPlateAsync(List<PlateItem> items, CancellationToken ct)
        {
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
