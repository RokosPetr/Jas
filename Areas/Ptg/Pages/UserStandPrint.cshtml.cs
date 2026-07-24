using Jas.Application.Abstractions;
using Jas.Data.JasMtzDb;
using Jas.Data.JasPdfDb;
using Jas.Helpers;
using Jas.Models.Ptg;
using Jas.Services;
using Microsoft.EntityFrameworkCore;
using System.Security.Claims;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Hosting;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Playwright;
using System.Globalization;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "PTG - jas,PTG - vo")]
    public class UserStandPrintModel : PageModel, IPieceStandModel
    {
        private readonly IImageStore _imageStore;
        private readonly JasMtzDbContext _mtzDb;
        private readonly JasPdfDbContext _pdfDb;
        private readonly IPdfService _pdfService;
        private readonly IRazorRenderer _renderer;
        private readonly IWebHostEnvironment _webHostEnvironment;

        public UserStandPrintModel(IImageStore imageStore, JasMtzDbContext mtzDb, JasPdfDbContext pdfDb, IPdfService pdfService, IRazorRenderer razorRenderer, IWebHostEnvironment webHostEnvironment)
        {
            _imageStore = imageStore;
            _mtzDb = mtzDb;
            _pdfDb = pdfDb;
            _pdfService = pdfService;
            _renderer = razorRenderer;
            _webHostEnvironment = webHostEnvironment;
        }

        public StandCompany? Stand { get; set; }
        public List<Plate> Plates { get; set; } = new();
        public List<PlateItem> PlateItems { get; set; } = new();
        [BindProperty(SupportsGet = true)]
        public DateTime? ChangeDate { get; set; }
        [BindProperty(SupportsGet = true)]
        public bool PrintQr { get; set; } = false;
        [BindProperty(SupportsGet = true)]
        public bool PrintPictures { get; set; } = false;
        [BindProperty(SupportsGet = true)]
        public bool Inline { get; set; } = false;
        [BindProperty(SupportsGet = true)]
        public bool ForceSaveToDisk { get; set; } = false;
        public bool VoPrice { get; set; } = false;
        public string? CssFonts { get; set; }
        [BindProperty(SupportsGet = true)]
        public string? Slug { get; set; }
        [BindProperty(SupportsGet = true, Name = "changeDateSegment")]
        public string? ChangeDateSegment { get; set; }
        [BindProperty(SupportsGet = true)]
        public string? RequestedFileName { get; set; }

        public IReadOnlyList<string> ChangeTexts { get; set; } = [];
        public string UserId { get; private set; } = string.Empty;

        public async Task<IActionResult> OnGetAsync(int id, CancellationToken ct)
        {
            ApplyLegacyQueryAliases();
            ApplyChangeDateSegment();
            RequestedFileName = NormalizeRequestedFileName(RequestedFileName);

            if (Request.Query.ContainsKey("handler") && string.Equals(Request.Query["handler"], "Pdf", StringComparison.OrdinalIgnoreCase))
            {
                return await OnGetPdfAsync(id, ct);
            }

            return await GetOrCreatePdfFileResultAsync(id, ct);
        }

        public async Task<IActionResult> OnGetPdfAsync(int id, CancellationToken ct)
        {
            return await GetOrCreatePdfFileResultAsync(id, ct);
        }

        public async Task<IActionResult> OnGetHtmlAsync(int id, CancellationToken ct)
        {
            ApplyLegacyQueryAliases();
            ApplyChangeDateSegment();

            await LoadStandDataAsync(id, ct);

            VoPrice = User.IsInRole("PTG - vo");

            var html = await _renderer.RenderViewToStringAsync("/Areas/Ptg/Pages/_PieceStandPrint.cshtml", this);

            return Content(html, "text/html");
        }

        private async Task LoadStandDataAsync(int id, CancellationToken ct)
        {
            Stand = null;
            Plates = new List<Plate>();
            PlateItems = new List<PlateItem>();
            ChangeTexts = new List<string>();

            var userId = User.FindFirstValue(ClaimTypes.NameIdentifier) ?? string.Empty;
            UserId = userId;

            var userStand = await _mtzDb.PtgUserStands
                .Include(s => s.PtgUserStandItems)
                .FirstOrDefaultAsync(s => s.Id == id && s.UserId == userId, ct);

            if (userStand is null)
                return;

            var items = userStand.PtgUserStandItems
                .OrderBy(i => i.SortOrder)
                .ThenBy(i => i.RegNumber)
                .ToList();

            var allRegs = items
                .Select(i => i.RegNumber)
                .Distinct(StringComparer.OrdinalIgnoreCase)
                .ToList();

            var priceTags = allRegs.Count > 0
                ? await _pdfDb.PdfPriceTags
                    .Where(p => allRegs.Contains(p.RegNumber))
                    .ToDictionaryAsync(p => p.RegNumber, p => p, StringComparer.OrdinalIgnoreCase, ct)
                : new Dictionary<string, PdfPriceTag>(StringComparer.OrdinalIgnoreCase);

            Stand = new StandCompany
            {
                IdStand = userStand.Id,
                Code = userStand.Id.ToString(),
                Name = userStand.Name,
                PiecePriceTag = true,
                PlatePriceTag = false,
            };

            Plates = new List<Plate>
            {
                new Plate
                {
                    IdPlate = 1,
                    IdStand = userStand.Id,
                    PlateOrder = 0,
                    RegNumberCount = items.Count,
                }
            };

            PlateItems = items
                .Select((item, index) =>
                {
                    priceTags.TryGetValue(item.RegNumber, out var tag);
                    return new PlateItem
                    {
                        IdPlateItem = item.Id,
                        IdPlate = 1,
                        RegNumber = item.RegNumber,
                        ItemName = tag?.Name ?? item.RegNumber,
                        ItemOrder = index,
                        TagName = tag?.Name,
                        TagDescription = tag?.Description,
                        SizeType1 = tag?.Size,
                        Price = tag?.Price,
                        PriceJas = tag?.PriceJas,
                        PriceNn = tag?.PriceNn,
                        Unit = tag?.Unit,
                        Orig_Name = tag?.OrigName,
                        Frost = tag?.Frost ?? false,
                        Rectification = tag?.Rectification ?? false,
                        Antislip = tag?.Antislip,
                        Abrasion = tag?.Abrasion,
                        Outlet = tag?.Outlet ?? false,
                        Surface = tag?.Surface,
                        Discount = tag?.Discount ?? false,
                        Discarded = tag?.Discarded ?? false,
                        ToSellout = tag?.InStockQuantity > 10 && tag?.Psku is 18 or 19 or 20 or 21 or 45,
                        TypeOrder = tag?.TypeOrder ?? 0,
                        Qr = tag?.Qr,
                    };
                })
                .ToList();
        }

        private async Task<IActionResult> GetOrCreatePdfFileResultAsync(int id, CancellationToken ct)
        {
            await LoadStandDataAsync(id, ct);

            var fileName = BuildPdfFileName(id);

            if (IsCurrentDateRequest() && !ForceSaveToDisk)
            {
                var pdfContent = await GeneratePdfAsync();
                return Inline
                    ? File(pdfContent, "application/pdf")
                    : File(pdfContent, "application/pdf", fileName);
            }

            var pdfDirectory = Path.Combine(_webHostEnvironment.WebRootPath, "pdf", "ptg");
            Directory.CreateDirectory(pdfDirectory);

            var filePath = Path.Combine(pdfDirectory, fileName);
            var shouldRegenerateFile = ChangeDate.HasValue || ChangeTexts.Count > 0;

            if (shouldRegenerateFile || !System.IO.File.Exists(filePath))
            {
                var pdfContent = await GeneratePdfAsync();
                await System.IO.File.WriteAllBytesAsync(filePath, pdfContent, ct);
            }

            return Inline
                ? PhysicalFile(filePath, "application/pdf")
                : PhysicalFile(filePath, "application/pdf", fileName);
        }

        private async Task<byte[]> GeneratePdfAsync()
        {
            var html = await _renderer.RenderViewToStringAsync("/Areas/Ptg/Pages/_PieceStandPrint.cshtml", this);

            return _pdfService.ConvertHtmlToPdf(html, DinkToPdf.Orientation.Portrait);
        }

        private bool IsCurrentDateRequest()
        {
            var effectiveDate = (ChangeDate ?? DateTime.Today).Date;
            return effectiveDate == DateTime.Today;
        }

        private string BuildPdfFileName(int id)
        {
            if (!string.IsNullOrWhiteSpace(RequestedFileName))
            {
                return RequestedFileName;
            }

            var name = Stand?.Name;
            if (string.IsNullOrWhiteSpace(name))
            {
                name = $"stojan-{id}";
            }

            var slug = TextUtils.Slugify(name);
            var datePart = (ChangeDate ?? DateTime.Today).ToString("yyyyMMdd");
            var qrPart = PrintQr ? "qr" : "noqr";
            var picturesPart = PrintPictures ? "pics" : "nopics";
            return $"{slug}-{UserId}-{id}-{datePart}-{qrPart}-{picturesPart}.pdf";
        }

        private void ApplyLegacyQueryAliases()
        {
            if (!Request.Query.ContainsKey("qrcode"))
            {
                return;
            }

            var qrCodeValue = Request.Query["qrcode"].ToString();
            if (bool.TryParse(qrCodeValue, out var printQr))
            {
                PrintQr = printQr;
            }
        }

        private void ApplyChangeDateSegment()
        {
            if (ChangeDate.HasValue)
            {
                return;
            }

            var changeDateSegment = ChangeDateSegment;
            if (string.IsNullOrWhiteSpace(changeDateSegment) && Request.Query.ContainsKey("changeDateSegment"))
            {
                changeDateSegment = Request.Query["changeDateSegment"].ToString();
            }

            if (string.IsNullOrWhiteSpace(changeDateSegment))
            {
                return;
            }

            if (DateTime.TryParseExact(
                changeDateSegment,
                "yyyyMMdd",
                CultureInfo.InvariantCulture,
                DateTimeStyles.None,
                out var parsedDate))
            {
                ChangeDate = parsedDate;
            }
        }

        private static string? NormalizeRequestedFileName(string? requestedFileName)
        {
            if (string.IsNullOrWhiteSpace(requestedFileName))
            {
                return null;
            }

            var fileName = Path.GetFileName(requestedFileName);
            if (!fileName.EndsWith(".pdf", StringComparison.OrdinalIgnoreCase))
            {
                fileName += ".pdf";
            }

            return fileName;
        }
    }
}
