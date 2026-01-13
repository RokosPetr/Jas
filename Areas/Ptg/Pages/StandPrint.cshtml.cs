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
    public class StandPrintModel : PageModel
    {
        private readonly IImageStore _imageStore;
        private readonly IStandDetailReader _standReader;
        private readonly IPdfService _pdfService;
        private readonly IRazorRenderer _renderer;

        private string? _standHtml;

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
            await LoadStandDataAsync(id, ct);
            return Page();
        }

        public async Task<IActionResult> OnGetPdfAsync(int id, CancellationToken ct)
        {
            if (_standHtml is null)
            {
                await LoadStandDataAsync(id, ct);

                _standHtml = (Stand.PlatePriceTag && !Stand.PiecePriceTag)
                    ? await _renderer.RenderViewToStringAsync("/Areas/Ptg/Pages/_PlateStandPrint.cshtml", this)
                    : await _renderer.RenderViewToStringAsync("/Areas/Ptg/Pages/_PieceStandPrint.cshtml", this);
            }

            var orientation = (Stand.PlatePriceTag && !Stand.PiecePriceTag)
                ? DinkToPdf.Orientation.Landscape
                : DinkToPdf.Orientation.Portrait;

            var pdfContent = _pdfService.ConvertHtmlToPdf(_standHtml, orientation);

            var fileName = $"PlatePrint_{id}.pdf";
            return File(pdfContent, "application/pdf", fileName);
        }

        private async Task LoadStandDataAsync(int id, CancellationToken ct)
        {
            var data = await _standReader.GetAsync(id, ct);

            Stand = data.Stand;
            Plates = data.Plates
                .OrderBy(p => (p.ProductGroupCount + p.RegNumberCount) > (PrintQr ? 15 : 12))
                .ThenBy(p => p.PlateOrder)
                .ToList();
            PlateItems = data.Items;
        }

    }
}
