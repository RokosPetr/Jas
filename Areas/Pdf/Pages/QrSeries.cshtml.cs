using Jas.Data.JasPdfDb;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.AspNetCore.WebUtilities;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Caching.Memory;
using System.Net;

using System.Drawing;
using System.Drawing.Imaging;

namespace Jas.Areas.Pdf.Pages
{
    // -------------- entity pro uložení vazby QR -> series --------------
    // Pokud už máš vlastní tabulku/entitu, nahraď tímto typem a logikou níže.
    public class PdfQrSeriesLink
    {
        public int Id { get; set; }

        public string CompanyKey { get; set; } = null!;
        public string CatalogKey { get; set; } = null!;

        // global_page_index (v rámci celého PDF)
        public int GlobalPageIndex { get; set; }

        // pq_value (QR text z PDF)
        public string Qr { get; set; } = null!;

        // vybraný kód série (slug), např. "cersanit-mille"
        public string SeriesCode { get; set; } = null!;

        public DateTime UpdatedUtc { get; set; } = DateTime.UtcNow;
    }

    public class QrSeriesModel : PageModel
    {
        public List<string> QrCodeImages = new();

        private readonly JasPdfDbContext _context;
        private readonly IWebHostEnvironment _hostingEnvironment;
        private readonly IHttpClientFactory _httpClientFactory;
        private readonly IMemoryCache _cache;

        // global_page_index -> (pq_value -> product IDs)
        public Dictionary<int, Dictionary<string, List<string>>> QrRegs { get; set; } = new();

        // global_page_index -> (pq_value -> serie slugs)
        public Dictionary<int, Dictionary<string, List<string>>> QrSeries { get; set; } = new();

        // productId -> serie slug (e.g. "4991048" -> "cersanit-mille")
        public Dictionary<string, string> RegSeries { get; set; } = new();

        // -------------- pro FORM (POST) --------------
        [BindProperty]
        public string Qr { get; set; } = "";

        [BindProperty]
        public int GlobalPageIndex { get; set; }

        [BindProperty]
        public string? SelectedSeries { get; set; }

        public string? FlashMessage { get; set; }

        public QrSeriesModel(
            JasPdfDbContext context,
            IWebHostEnvironment webHostEnvironment,
            IHttpClientFactory httpClientFactory,
            IMemoryCache cache)
        {
            _context = context;
            _hostingEnvironment = webHostEnvironment;
            _httpClientFactory = httpClientFactory;
            _cache = cache;
        }

        public async Task<IActionResult> OnGetAsync(string companyKey, string catalogKey)
        {
            await LoadDictionariesAsync(companyKey, catalogKey);
            return Page();
        }

        // -------------- POST handler na uložení --------------
        public async Task<IActionResult> OnPostSaveAsync(string company, string catalog)
        {
            if (string.IsNullOrWhiteSpace(Qr))
            {
                ModelState.AddModelError("", "Chybí QR (pq_value).");
                await LoadDictionariesAsync(company, catalog);
                return Page();
            }

            if (string.IsNullOrWhiteSpace(SelectedSeries))
            {
                ModelState.AddModelError("", "Vyber kód série.");
                await LoadDictionariesAsync(company, catalog);
                return Page();
            }

            await LoadDictionariesAsync(company, catalog);

            // validace: vybraná série musí být v nabídce pro danou stránku+QR
            if (!QrSeries.TryGetValue(GlobalPageIndex, out var qrMap) ||
                !qrMap.TryGetValue(Qr, out var allowed) ||
                !allowed.Contains(SelectedSeries))
            {
                ModelState.AddModelError("", "Vybraný kód série není v nabídce pro tento QR.");
                return Page();
            }

            // upsert podle (company, catalog, global_page_index, qr)
            var existing = await _context.Set<PdfQrSeriesLink>()
                .FirstOrDefaultAsync(x =>
                    x.CompanyKey == company &&
                    x.CatalogKey == catalog &&
                    x.GlobalPageIndex == GlobalPageIndex &&
                    x.Qr == Qr);

            if (existing == null)
            {
                existing = new PdfQrSeriesLink
                {
                    CompanyKey = company,
                    CatalogKey = catalog,
                    GlobalPageIndex = GlobalPageIndex,
                    Qr = Qr,
                    SeriesCode = SelectedSeries!,
                    UpdatedUtc = DateTime.UtcNow
                };
                _context.Add(existing);
            }
            else
            {
                existing.SeriesCode = SelectedSeries!;
                existing.UpdatedUtc = DateTime.UtcNow;
            }

            await _context.SaveChangesAsync();

            FlashMessage = $"Uloženo: str.{GlobalPageIndex} / {Qr} → {SelectedSeries}";

            return RedirectToPage(new { company, catalog });
        }

        /// <summary>
        /// Handler: vrátí QR obrázek kódující přímo string "qr".
        /// URL: /{company}/{catalog}/qrseries?handler=Qr&qr=XYZ
        /// </summary>
        public Task<IActionResult> OnGetQrAsync(string qr)
        {
            return null;
            //if (string.IsNullOrWhiteSpace(qr))
            //    return Task.FromResult<IActionResult>(BadRequest("Missing qr."));

            //QREncoder encoder = new QREncoder();
            //bool[,] qrMatrix = encoder.Encode(qr);

            //QRSaveBitmapImage bitmapImage = new(qrMatrix)
            //{
            //    ModuleSize = 8,
            //    QuietZone = 5
            //};

            //using Bitmap qrCodeImage = bitmapImage.CreateQRCodeBitmap();
            //var outputStream = new MemoryStream();
            //qrCodeImage.Save(outputStream, ImageFormat.Jpeg);
            //outputStream.Seek(0, SeekOrigin.Begin);

            //string safe = MakeSafeFilePart(qr);
            //string fileName = $"qr_{safe}.jpg";

            //return Task.FromResult<IActionResult>(File(outputStream, "image/jpeg", fileName));
        }

        private static string MakeSafeFilePart(string s)
        {
            foreach (var ch in Path.GetInvalidFileNameChars())
                s = s.Replace(ch, '_');
            return s.Length > 80 ? s.Substring(0, 80) : s;
        }

        // -------------- CORE: načtení + výpočet slovníků --------------
        private async Task LoadDictionariesAsync(string companyKey, string catalogKey)
        {
            var data = await _context.ViPdfQrRedirects
                .Where(x => x.CompanyKey == companyKey
                         && x.CatalogKey == catalogKey
                         && x.ConcatPtValue != null
                         && x.GlobalPageIndex != null)   // ⬅️ důležité
                .Select(x => new
                {
                    GlobalPageIndex = x.GlobalPageIndex!.Value,  // ⬅️ přetypování
                    x.PqValue,
                    x.ConcatPtValue
                })
                .ToListAsync();

            // global_page_index -> (pq_value -> product IDs)
            QrRegs = data
                .GroupBy(x => x.GlobalPageIndex)
                .ToDictionary(
                    g => g.Key,
                    g => g
                        .GroupBy(x => x.PqValue!)
                        .ToDictionary(
                            gg => gg.Key,
                            gg => gg
                                .SelectMany(x => x.ConcatPtValue!
                                    .Split(',', StringSplitOptions.RemoveEmptyEntries))
                                .Select(v => v.Trim())
                                .Where(v => !string.IsNullOrWhiteSpace(v))
                                .Distinct()
                                .ToList()
                        )
                );

            var client = _httpClientFactory.CreateClient();
            client.Timeout = TimeSpan.FromSeconds(15);

            var htmlPerProductId = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase);

            QrSeries = new Dictionary<int, Dictionary<string, List<string>>>();
            RegSeries = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase);

            foreach (var pageEntry in QrRegs)
            {
                int globalPageIndex = pageEntry.Key;
                var qrMap = pageEntry.Value;

                var qrSeriesForPage = new Dictionary<string, List<string>>(StringComparer.OrdinalIgnoreCase);

                foreach (var qrEntry in qrMap)
                {
                    string pqValue = qrEntry.Key;
                    var productIds = qrEntry.Value;

                    var seriesSlugs = new HashSet<string>(StringComparer.OrdinalIgnoreCase);

                    foreach (var id in productIds)
                    {
                        if (!htmlPerProductId.TryGetValue(id, out var html))
                        {
                            var cacheKey = $"koupelnyjas:product-html:{id}";

                            if (!_cache.TryGetValue(cacheKey, out html))
                            {
                                var url = $"https://www.koupelny-jas.cz/eshop/p/x-i{id}";
                                try
                                {
                                    html = await client.GetStringAsync(url);
                                    _cache.Set(cacheKey, html, TimeSpan.FromHours(12));
                                }
                                catch
                                {
                                    continue;
                                }
                            }

                            htmlPerProductId[id] = html!;
                        }
                        var serieSlug = TryExtractSerieSlug(htmlPerProductId[id]);
                        if (!string.IsNullOrWhiteSpace(serieSlug))
                        {
                            // finální URL pro QR / výstup
                            var serieUrl =
                                $"https://www.koupelny-jas.cz/produkty" +
                                $"?utm_source=qr-katalog" +
                                $"&utm_medium=katalog-general" +
                                $"&utm_campaign={catalogKey}" +
                                $"&serie={serieSlug}";

                            // do QrSeries ukládáme URL (ne slug)
                            seriesSlugs.Add(serieUrl);

                            // RegSeries zůstává slug (productId -> slug)
                            RegSeries[id] = serieSlug;
                        }
                    }

                    qrSeriesForPage[pqValue] = seriesSlugs.OrderBy(x => x).ToList();
                }

                QrSeries[globalPageIndex] = qrSeriesForPage;
            }
        }

        private static string? TryExtractSerieSlug(string html)
        {
            var idx = html.IndexOf("class=\"level1\"", StringComparison.OrdinalIgnoreCase);
            while (idx >= 0)
            {
                var hrefIdx = html.IndexOf("href=\"", idx, StringComparison.OrdinalIgnoreCase);
                if (hrefIdx < 0) break;

                hrefIdx += "href=\"".Length;
                var hrefEnd = html.IndexOf("\"", hrefIdx, StringComparison.OrdinalIgnoreCase);
                if (hrefEnd < 0) break;

                var hrefRaw = html.Substring(hrefIdx, hrefEnd - hrefIdx);
                var href = WebUtility.HtmlDecode(hrefRaw);

                if (href.Contains("serie%5B0%5D=", StringComparison.OrdinalIgnoreCase) ||
                    href.Contains("serie[0]=", StringComparison.OrdinalIgnoreCase))
                {
                    var uri = href.StartsWith("http", StringComparison.OrdinalIgnoreCase)
                        ? new Uri(href)
                        : new Uri("https://www.koupelny-jas.cz" + href);

                    var query = QueryHelpers.ParseQuery(uri.Query);

                    if (query.TryGetValue("serie[0]", out var v) && v.Count > 0)
                        return v[0];

                    if (query.TryGetValue("serie%5B0%5D", out var v2) && v2.Count > 0)
                        return v2[0];
                }

                idx = html.IndexOf("class=\"level1\"", hrefEnd, StringComparison.OrdinalIgnoreCase);
            }

            return null;
        }
    }
}
