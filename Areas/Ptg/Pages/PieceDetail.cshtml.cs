using Jas.Application.Abstractions;
using Jas.Application.Abstractions.Ptg;
using Jas.Models.Ptg;
using Jas.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Playwright;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "PTG - admin,PTG - user")]
    public class PieceDetailModel : PageModel
    {
        private readonly IImageStore _imageStore;
        private readonly IStandDetailReader _standReader;
        private readonly IRazorRenderer _renderer;

        public PieceDetailModel(
            IImageStore imageStore,
            IStandDetailReader standReader,
            IRazorRenderer renderer)
        {
            _imageStore = imageStore;
            _standReader = standReader;
            _renderer = renderer;
        }

        public StandCompany? Stand { get; set; }
        public List<Plate> Plates { get; set; } = new();
        public List<PlateItem> PlateItems { get; set; } = new();
        public Dictionary<string, string> QrTagPng { get; set; } = new();
        public Dictionary<string, string> TagPng { get; set; } = new();

        // jednoduchý cache klíè = stojan
        private static readonly object _tagCacheLock = new();

        private static readonly Dictionary<int, (DateTime Created, Dictionary<string, string> Qr, Dictionary<string, string> NoQr)>
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
            (DateTime Created, Dictionary<string, string> Qr, Dictionary<string, string> NoQr) cached;

            lock (_tagCacheLock)
            {
                if (_tagCache.TryGetValue(standId, out cached)
                    && DateTime.UtcNow - cached.Created < CacheTtl)
                {
                    QrTagPng = new Dictionary<string, string>(cached.Qr ?? new());
                    TagPng   = new Dictionary<string, string>(cached.NoQr ?? new());
                    return;
                }
            }

            await GenerateTagsAsync(ct);

            lock (_tagCacheLock)
            {
                _tagCache[standId] = (DateTime.UtcNow,
                    new Dictionary<string, string>(QrTagPng),
                    new Dictionary<string, string>(TagPng));
            }
        }

        private async Task GenerateTagsAsync(CancellationToken ct)
        {
            var standPrintModel = new StandPrintModel(_imageStore, _standReader, null!, _renderer)
            {
                Stand = Stand,
                Plates = Plates,
                PlateItems = PlateItems,
                PrintQr = true
            };

            using var playwright = await Playwright.CreateAsync();
            await using var browser = await playwright.Chromium.LaunchAsync(new() { Headless = true });
            var context = await browser.NewContextAsync(new()
            {
                ViewportSize = new() { Width = 1200, Height = 800 },
                DeviceScaleFactor = 1
            });
            var page = await context.NewPageAsync();

            QrTagPng.Clear();
            TagPng.Clear();

            async Task FillDictAsync(bool printQr, Dictionary<string, string> target)
            {
                standPrintModel.PrintQr = printQr;
                var html = await _renderer.RenderViewToStringAsync("/Areas/Ptg/Pages/_PieceStandPrint.cshtml", standPrintModel);
                await page.SetContentAsync(html);

                await page.EvaluateAsync(@"async () => {
                if (document.fonts && document.fonts.ready) {
                    await document.fonts.ready;
                }
                void(document.body.offsetHeight);
            }");

                using var sem = new SemaphoreSlim(6);
                var tasks = PlateItems
                    .Where(i => !string.IsNullOrWhiteSpace(i.RegNumber))
                    .Select(async item =>
                    {
                        await sem.WaitAsync(ct);
                        try
                        {
                            var selector = "#reg_" + item.RegNumber;
                            var locator = page.Locator(selector);

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

            await FillDictAsync(printQr: true, target: QrTagPng);
            await FillDictAsync(printQr: false, target: TagPng);
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

            await EnsureTagsFromCacheOrGenerateAsync(id, ct);

            return new JsonResult(new { CountQr = QrTagPng.Count, CountTag = TagPng.Count });
        }
    }
}