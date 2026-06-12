using Jas.Application.Abstractions;
using Jas.Application.Abstractions.Ptg;
using Jas.Models.Ptg;
using Jas.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Hosting;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Playwright;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "PTG - jas,PTG - vo")]
    public class PieceDetailModel : PageModel
    {
        private readonly IImageStore _imageStore;
        private readonly IStandDetailReader _standReader;
        private readonly IRazorRenderer _renderer;
        private readonly IWebHostEnvironment _webHostEnvironment;

        public PieceDetailModel(
            IImageStore imageStore,
            IStandDetailReader standReader,
            IRazorRenderer renderer,
            IWebHostEnvironment webHostEnvironment)
        {
            _imageStore = imageStore;
            _standReader = standReader;
            _renderer = renderer;
            _webHostEnvironment = webHostEnvironment;
        }

        public StandCompany? Stand { get; set; }
        public List<Plate> Plates { get; set; } = new();
        public List<PlateItem> PlateItems { get; set; } = new();
        public Dictionary<string, string> MoTagPng { get; set; } = new();
        public Dictionary<string, string> VoTagPng { get; set; } = new();

        // jednoduchý cache klíè = stojan
        private static readonly object _tagCacheLock = new();

        private static readonly Dictionary<int, (DateTime Created, Dictionary<string, string> MoTags, Dictionary<string, string> VoTags)>
            _tagCache;

        static PieceDetailModel()
        {
            _tagCache = new();
        }

        private static readonly TimeSpan CacheTtl = TimeSpan.FromHours(1);

        public async Task<IActionResult> OnGetAsync(int id, CancellationToken ct)
        {
            var data = await _standReader.GetAsync(id, ct);
            Stand = data.Stand;
            Plates = data.Plates;
            PlateItems = data.Items;

            // Pokud má stojan více plat, slouèíme všechny položky do jednoho plata.
            // Pro potøeby detailu kusovek nepotøebujeme rozlišovat jednotlivá plata.
            GroupAllItemsIntoSinglePlate();

            var firstPlate = Plates.FirstOrDefault();
            if (firstPlate is null)
                return Page();

            var firstPlateItems = PlateItems.Where(i => i.IdPlate == firstPlate.IdPlate).ToList();
            foreach (var it in firstPlateItems)
                it.Picture = _imageStore.ProductPath(it.RegNumber);

            await EnsureHasImagesAsync(PlateItems.Where(i => i.IdPlate == firstPlate.IdPlate), ct);

            // nejdøív zkusit cache, Playwright jen když je potøeba
            await EnsureTagsFromCacheOrGenerateAsync(id, ct);

            return Page();
        }

        private async Task EnsureTagsFromCacheOrGenerateAsync(int standId, CancellationToken ct)
        {
            (DateTime Created, Dictionary<string, string> MoTags, Dictionary<string, string> VoTags) cached;

            lock (_tagCacheLock)
            {
                if (_tagCache.TryGetValue(standId, out cached)
                    && DateTime.UtcNow - cached.Created < CacheTtl)
                {
                    MoTagPng = new Dictionary<string, string>(cached.MoTags ?? new());
                    VoTagPng = new Dictionary<string, string>(cached.VoTags ?? new());
                    return;
                }
            }

            await GenerateTagsAsync(ct);

            lock (_tagCacheLock)
            {
                _tagCache[standId] = (DateTime.UtcNow,
                    new Dictionary<string, string>(MoTagPng),
                    new Dictionary<string, string>(VoTagPng));
            }
        }

        private async Task GenerateTagsAsync(CancellationToken ct)
        {
            var standPrintModel = new StandPrintModel(_imageStore, _standReader, null!, _renderer, _webHostEnvironment)
            {
                Stand = Stand,
                Plates = Plates,
                PlateItems = PlateItems,
                PrintQr = true,
                VoPrice = User.IsInRole("PTG - vo")
            };

            using var playwright = await Playwright.CreateAsync();
            await using var browser = await playwright.Chromium.LaunchAsync(new() { Headless = true });
            var context = await browser.NewContextAsync(new()
            {
                ViewportSize = new() { Width = 1200, Height = 800 },
                DeviceScaleFactor = 1
            });
            var page = await context.NewPageAsync();

            MoTagPng.Clear();
            VoTagPng.Clear();

            async Task FillDictAsync(bool printQr, Dictionary<string, string> target, bool voPrice)
            {
                standPrintModel.PrintQr = printQr;
                standPrintModel.VoPrice = voPrice;
                var html = await _renderer.RenderViewToStringAsync("/Areas/Ptg/Pages/_PieceStandPrint.cshtml", standPrintModel);
                await page.SetContentAsync(html);

                await page.EvaluateAsync(@"async () => {
                    if (document.fonts && document.fonts.ready) {
                        await document.fonts.ready;
                    }
                    void(document.body.offsetHeight);
                }");

                using var sem = new SemaphoreSlim(6);

                var itemsByReg = PlateItems
                    .Where(i => !string.IsNullOrWhiteSpace(i.RegNumber))
                    .GroupBy(i => i.RegNumber)
                    .Select(g => g.First())
                    .ToList();

                var tasks = itemsByReg
                    .Select(async item =>
                    {
                        await sem.WaitAsync(ct);
                        try
                        {
                            var selector = "#reg_" + item.RegNumber;
                            var locator = page.Locator(selector).First;

                            if (await locator.CountAsync() == 0)
                                return;

                            var pngBytes = await locator.ScreenshotAsync(new LocatorScreenshotOptions
                            {
                                Type = ScreenshotType.Png
                            });

                            var base64 = Convert.ToBase64String(pngBytes);
                            target[item.RegNumber] = base64;
                        }
                        finally
                        {
                            sem.Release();
                        }
                    });

                await Task.WhenAll(tasks);
            }

            await FillDictAsync(printQr: true, target: MoTagPng, voPrice: false);
            await FillDictAsync(printQr: false, target: VoTagPng, voPrice: true);
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

        public async Task<IActionResult> OnGetPieceTagPngAsync(int id, CancellationToken ct)
        {
            var data = await _standReader.GetAsync(id, ct);
            Stand = data.Stand;
            Plates = data.Plates;
            PlateItems = data.Items;

            // Stejná logika seskupení jako v OnGetAsync, aby generování tagù
            // vycházelo z jednoho „spojeného“ plata.
            GroupAllItemsIntoSinglePlate();

            await EnsureTagsFromCacheOrGenerateAsync(id, ct);

            return new JsonResult(new { CountQr = MoTagPng.Count, CountTag = VoTagPng.Count });
        }

        /// <summary>
        /// Pokud stojan obsahuje více plat, seskupí všechny položky do jednoho plata
        /// tak, že ponechá první podle PlateOrder a všem položkám nastaví jeho IdPlate.
        /// </summary>
        private void GroupAllItemsIntoSinglePlate()
        {
            if (Plates == null || Plates.Count <= 1)
            {
                return;
            }

            var mainPlate = Plates
                .OrderBy(p => p.PlateOrder)
                .ThenBy(p => p.IdPlate)
                .First();

            foreach (var item in PlateItems)
            {
                item.IdPlate = mainPlate.IdPlate;
            }

            Plates = new List<Plate> { mainPlate };
        }
    }

}