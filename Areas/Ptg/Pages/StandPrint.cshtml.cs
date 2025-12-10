using Jas.Application.Abstractions;
using Jas.Application.Abstractions.Ptg;
using Jas.Models.Ptg;
using Jas.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "PTG - admin,PTG - user")]
    public class StandPrintModel : PageModel
    {
        private readonly IImageStore _imageStore;
        private readonly IStandDetailReader _standReader;
        private readonly IPdfService _pdfService;
        private readonly IRazorRenderer _renderer;


        public StandPrintModel(IImageStore imageStore, IStandDetailReader standReader, IPdfService pdfService, IRazorRenderer razorRenderer)
        {
            _imageStore = imageStore;
            _standReader = standReader;
            _pdfService = pdfService;
            _renderer = razorRenderer;
        }

        public StandCompany? Stand { get; set; }
        public List<Plate> Plates { get; set; } = new();
        public List<PlateItem> PlateItems { get; set; } = new();
        [BindProperty(SupportsGet = true)]
        public bool PrintQr { get; set; } = false;
        [BindProperty(SupportsGet = true)]
        public bool PrintPictures { get; set; } = false;
        public string? CssFonts { get; set; }
        public async Task<IActionResult> OnGetAsync(int id, CancellationToken ct)
        {
            var data = await _standReader.GetAsync(id, ct);
            Stand = data.Stand;
            Plates = data.Plates
                .OrderBy(p => (p.ProductGroupCount + p.RegNumberCount) > (PrintQr ? 15 : 12))
                .ThenBy(p => p.PlateOrder)
                .ToList();
            PlateItems = data.Items;

            CssFonts = GetCssFonts();
            
            return Page();
        }

        public async Task<IActionResult> OnGetPdfAsync(int id, CancellationToken ct)
        {
            // 1) načtení dat stejně jako v OnGetAsync
            var data = await _standReader.GetAsync(id, ct);
            Stand = data.Stand;
            Plates = data.Plates
                .OrderBy(p => (p.ProductGroupCount + p.RegNumberCount) > (PrintQr ? 15 : 12))
                .ThenBy(p => p.PlateOrder)
                .ToList();
            PlateItems = data.Items;

            CssFonts = GetCssFonts(true);

            // 2) vygenerování HTML z Razor view
            // u Razor Pages většinou funguje plná cesta k view
            // případně upravte podle struktury projektu
            var htmlContent = (Stand.PlatePriceTag && !Stand.PiecePriceTag) ?
                await _renderer.RenderViewToStringAsync("/Areas/Ptg/Pages/_PlateStandPrint.cshtml", this)
                : await _renderer.RenderViewToStringAsync("/Areas/Ptg/Pages/_PieceStandPrint.cshtml", this);

            // 3) převod HTML -> PDF
            byte[] pdfContent = _pdfService.ConvertHtmlToPdf(htmlContent, (Stand.PlatePriceTag && !Stand.PiecePriceTag) ? DinkToPdf.Orientation.Landscape : DinkToPdf.Orientation.Portrait);

            // 4) návrat PDF jako download
            var fileName = $"PlatePrint_{id}.pdf";
            return File(pdfContent, "application/pdf", fileName);
        }

        public async Task<IActionResult> OnGetHtmlAsync(int id, CancellationToken ct)
        {
            await OnGetAsync(id, ct); // načti data stejně jako normální stránka
            return Page();            // vrátí HTML samotné Razor Page
        }

        private string  GetCssFonts(bool isPdf = false)
        {
            string path = isPdf ? Path.Combine(Directory.GetCurrentDirectory(), "wwwroot") : "";
            return "@font-face{font-family:\"OpenSansExtrabold\";src:url(\"" + path.Replace("\\","/")  + "/fonts/OpenSans-ExtraBold.ttf\") format(\"truetype\");}";
        }
    }
}
