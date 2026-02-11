using System.Data;
using ClosedXML.Excel;
using Jas.Application.Abstractions.Ptg;
using Jas.Models.Ptg;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using System.Globalization;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "PTG - jas,PTG - vo")]
    public class StandXlsModel : PageModel
    {
        private readonly IStandDetailReader _standReader;

        public StandCompany? Stand { get; private set; }
        public List<Plate> Plates { get; private set; } = new();
        public List<PlateItem> PlateItems { get; private set; } = new();

        public StandXlsModel(IStandDetailReader standReader)
        {
            _standReader = standReader;
        }

        public async Task<IActionResult> OnGetDownloadAsync(int id, CancellationToken ct)
        {
            var data = await _standReader.GetAsync(id, ct);
            Stand = data.Stand;
            Plates = data.Plates;
            PlateItems = data.Items;

            using var workbook = new XLWorkbook();
            var ws = workbook.Worksheets.Add("Stojan");
            ws.Style.Font.FontName = "Arial";
            ws.Column(4).Style.NumberFormat.Format = "#,##0.00 \"Kè\"";
            ws.Column(4).Style.Alignment.Horizontal = XLAlignmentHorizontalValues.Right;
            ws.Range("B1:E1").Merge();

            var row = 1;

            // Øádek 1: GALAXY 4 (MO+VO) (2/3)
            // => Stand.Name (Stand.Code) (Stand.Type)
            ws.Cell(row, 2).Value = $"{Stand?.Name} ({Stand?.Code}) ({Stand?.Type})";
            ws.Row(row).Style.Font.SetBold();
            ws.Row(row).Style.Font.SetFontSize(14);
            row++;
            row++;

            var czech = CultureInfo.GetCultureInfo("cs-CZ");

            // Pro každý plát
            foreach (var (plate, plateIndex) in Plates
                         .OrderBy(p => p.PlateOrder)
                         .Select((p, idx) => (p, idx + 1)))
            {
                // Øádek: èíslo plátu + název plátu
                // napø.: "1    ARCE"
                ws.Cell(row, 1).Value = plateIndex;
                ws.Cell(row, 2).Value = plate.Description ?? string.Empty;
                ws.Row(row).Style.Font.SetBold();
                ws.Row(row).Style.Font.SetFontSize(12);
                row++;

                var itemsForPlate = PlateItems
                    .Where(i => i.IdPlate == plate.IdPlate)
                    .OrderBy(i => i.TypeOrder)
                    .ThenBy(i => i.ItemOrder)
                    .ToList();

                // Položky plátu
                var itemIndex = 1;
                foreach (var item in itemsForPlate)
                {
                    ws.Cell(row, 1).Value = itemIndex;
                    ws.Cell(row, 2).Value = item.RegNumber ?? string.Empty;
                    ws.Cell(row, 3).Value = (item.TagName ?? string.Empty) + " " + (item.SizeType2 ?? string.Empty);

                    // Cena jako "499 Kè", bez jednotky z databáze;
                    // pokud chceš mít mìnu podle item.Unit, mùžeš složit napø. $"{item.Price} {item.Unit}"
                    var priceText = string.Format(czech, "{0:0}", item.Price) + " Kè";
                    ws.Cell(row, 4).Value = priceText;

                    row++;
                    itemIndex++;
                }

                // Prázdný øádek mezi pláty
                row++;
            }

            ws.Columns().AdjustToContents();

            using var stream = new MemoryStream();
            workbook.SaveAs(stream);
            stream.Position = 0;

            var fileName = $"Stojan_{Stand?.IdStand ?? id}.xlsx";
            return File(
                fileContents: stream.ToArray(),
                contentType: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                fileDownloadName: fileName);
        }
    }
}