using Jas.Application.Abstractions;
using Jas.Application.Abstractions.Ptg;
using Jas.Helpers;
using Jas.Models.Ptg;
using Jas.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Hosting;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Playwright;
using System.Globalization;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [AllowAnonymous]
    public class StandPrintModel : PageModel, IPieceStandModel
    {
        private readonly IImageStore _imageStore;
        private readonly IStandDetailReader _standReader;
        private readonly IPdfService _pdfService;
        private readonly IRazorRenderer _renderer;
        private readonly IWebHostEnvironment _webHostEnvironment;

        private string? _standHtml;

        public StandPrintModel(IImageStore imageStore, IStandDetailReader standReader, IPdfService pdfService, IRazorRenderer razorRenderer, IWebHostEnvironment webHostEnvironment)
        {
            _imageStore = imageStore;
            _standReader = standReader;
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
        [BindProperty(SupportsGet = true)]
        public string? Ico { get; set; }

        public IReadOnlyList<string> ChangeTexts { get; set; } = [];

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

            var html = (Stand!.PlatePriceTag && !Stand.PiecePriceTag)
                ? await _renderer.RenderViewToStringAsync("/Areas/Ptg/Pages/_PlateStandPrint.cshtml", this)
                : await _renderer.RenderViewToStringAsync("/Areas/Ptg/Pages/_PieceStandPrint.cshtml", this);

            return Content(html, "text/html");
        }

        private async Task LoadStandDataAsync(int id, CancellationToken ct)
        {
            var data = await _standReader.GetAsync(id, ct, ChangeDate);

            Stand = data.Stand;
            Plates = data.Plates
                .OrderBy(p => (p.ProductGroupCount + p.RegNumberCount) > (PrintQr ? 15 : 12))
                .ThenBy(p => p.PlateOrder)
                .ToList();
            PlateItems = data.Items;
            ChangeTexts = data.ChangeTexts?
                .Where(changeText => !string.IsNullOrWhiteSpace(changeText))
                .ToList()
                ?? [];
        }

        private async Task<IActionResult> GetOrCreatePdfFileResultAsync(int id, CancellationToken ct)
        {
            if (string.IsNullOrEmpty(Ico) && !(User.Identity?.IsAuthenticated ?? false))
            {
                return Challenge();
            }

            VoPrice = string.IsNullOrEmpty(Ico)
                ? User.IsInRole("PTG - vo")
                : !string.Equals(Ico, "27792803", StringComparison.Ordinal);

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
            _standHtml = (Stand!.PlatePriceTag && !Stand.PiecePriceTag)
                ? await _renderer.RenderViewToStringAsync("/Areas/Ptg/Pages/_PlateStandPrint.cshtml", this)
                : await _renderer.RenderViewToStringAsync("/Areas/Ptg/Pages/_PieceStandPrint.cshtml", this);

            var orientation = (Stand.PlatePriceTag && !Stand.PiecePriceTag)
                ? DinkToPdf.Orientation.Landscape
                : DinkToPdf.Orientation.Portrait;

            return _pdfService.ConvertHtmlToPdf(_standHtml, orientation);
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
            return $"{slug}-{id}-{datePart}-{qrPart}-{picturesPart}.pdf";
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
