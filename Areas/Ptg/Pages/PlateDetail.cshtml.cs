using Jas.Application.Abstractions;
using Jas.Application.Abstractions.Ptg;
using Jas.Infrastructure.Images;
using Jas.Models.Ptg;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using System.Data;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "PTG - admin,PTG - user")]
    public class PlateDetailModel : PageModel
    {
        private readonly IImageStore _imageStore;
        private readonly IStandDetailReader _standReader;

        public PlateDetailModel(IImageStore imageStore, IStandDetailReader standReader)
        {
            _imageStore = imageStore;
            _standReader = standReader;
        }

        public StandCompany? Stand { get; set; }
        public List<Plate> Plates { get; set; } = new();
        public List<PlateItem> PlateItems { get; set; } = new();
        public Plate? CurrentPlate { get; set; }
        [BindProperty(SupportsGet = true)]
        public int Plate { get; set; } = 1;
        [BindProperty(SupportsGet = true)]
        public int? IdPlate { get; set; }
        public PlateContentVm? InitialVm { get; private set; }

        public async Task<IActionResult> OnGetAsync(int id, CancellationToken ct)
        {
            var data = await _standReader.GetAsync(id, ct);
            Stand = data.Stand;
            Plates = data.Plates;
            PlateItems = data.Items;

            if (IdPlate.HasValue)
            {
                var index = Plates.FindIndex(p => p.IdPlate == IdPlate.Value);
                if (index >= 0)
                {
                    Plate = index + 1;
                }
            }

            if (Plate < 1) Plate = 1;
            if (Plate > Plates.Count) Plate = Plates.Count;
            CurrentPlate = Plates[Plate - 1];

            InitialVm = await BuildVmAsync(Plate, ct);

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

            var vm = await BuildVmAsync(plate, HttpContext.RequestAborted);

            // Tady přepočítáme HasImage „na tvrdo“ jen pro aktuální plát
            var requestCt = HttpContext.RequestAborted; // nebo použij rovnou 'cancellationToken'
            await EnsureHasImageForCurrentPlateAsync(vm.Items, requestCt);

            return Partial("_PlateContent", vm);
        }

        private async Task<PlateContentVm> BuildVmAsync(int plateIndex, CancellationToken ct)
        {
            var current = Plates[plateIndex - 1];

            var vm = new PlateContentVm
            {
                StandId = Stand!.IdStand,
                StandName = Stand!.Name,
                StandType = Stand!.Type,
                PlateIndex = plateIndex,
                PlateDescription = current.Description!,
                TotalPlates = Plates.Count,
                ImgUrl = string.IsNullOrEmpty(current.ImgUrl) ? "/images/no-picture.png" : current.ImgUrl,
                Items = PlateItems
                    .Where(i => i.IdPlate == current.IdPlate)
                    .OrderBy(i => i.ItemOrder)
                    .Select(i => { i.Picture = _imageStore.ProductPath(i.RegNumber); return i; })
                    .ToList()
            };

            await EnsureHasImageForCurrentPlateAsync(vm.Items, ct);
            return vm;
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
        public string StandName { get; set; }
        public string StandCode { get; set; }
        public int StandType { get; set; }
        public int PlateIndex { get; set; }
        public string PlateDescription { get; set; }
        public int TotalPlates { get; set; }
        public string? ImgUrl { get; set; }
        public List<PlateItem> Items { get; set; } = new();
        public Dictionary<string, string> QrTagPng { get; set; } = new();
        public Dictionary<string, string> TagPng { get; set; } = new();
    }
}
