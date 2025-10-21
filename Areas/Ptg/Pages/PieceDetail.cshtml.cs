using Jas.Application.Abstractions; // IImageStore
using Jas.Application.Abstractions.Ptg;
using Jas.Models.Ptg;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "Administrator,PTG - admin,PTG - uživatel")]
    public class PieceDetailModel : PageModel
    {
        private readonly IImageStore _imageStore;
        private readonly IStandDetailReader _standReader;

        public PieceDetailModel(IImageStore imageStore, IStandDetailReader standReader)
        {
            _imageStore = imageStore;
            _standReader = standReader;
        }

        public StandCompany? Stand { get; set; }
        public List<Plate> Plates { get; set; } = new();
        public List<PlateItem> PlateItems { get; set; } = new();

        public async Task<IActionResult> OnGetAsync(int id, CancellationToken ct)
        {
            var data = await _standReader.GetAsync(id, ct);
            Stand = data.Stand;
            Plates = data.Plates;
            PlateItems = data.Items;

            var firstPlate = Plates.FirstOrDefault();
            if (firstPlate is null)
                return Page();

            await EnsureHasImagesAsync(PlateItems.Where(i => i.IdPtPlate == firstPlate.Id), ct);
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

        private sealed class StandDetailCache
        {
            public StandCompany? Stand { get; init; }
            public List<Plate> Plates { get; init; } = new();
            public List<PlateItem> Items { get; init; } = new();
        }
    }
}