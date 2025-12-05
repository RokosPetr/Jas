using Jas.Helpers;
using QRCoder;
using System.Drawing;

namespace Jas.Models.Ptg
{
    public class PlateItem
    {
        public int IdPlateItem { get; set; }
        public int IdPlate { get; set; }
        public string RegNumber { get; set; }
        public string ItemName { get; set; }
        public int ItemOrder { get; set; }
        public bool SeriesItem { get; set; }

        // Z pdf_price_tag
        public string? TagName { get; set; }
        public string? SizeType1 { get; set; }
        public string? SizeType2 { get; set; }
        public int? Price { get; set; }
        public int? PriceJas { get; set; }
        public int? PriceNn { get; set; }
        public string? Unit { get; set; }
        public string? Orig_Name { get; set; }
        public bool Frost { get; set; }
        public bool Rectification { get; set; }
        public string? Antislip { get; set; }
        public string? Abrasion { get; set; }
        public bool Outlet { get; set; }
        public string? Surface { get; set; }
        public bool Discount { get; set; }
        public bool Discarded { get; set; }
        public string? ProductType { get; set; }
        public int TypeOrder { get; set; }
        public string? Qr { get; set; }
        public string? PlateQr { get; set; }

        // Obrázek (lokální cesta / relativní cesta k obrázku)
        public string? Picture { get; set; }

        // Vrací upravenou URL (např. s CDN prefixem apod.)
        public string? ImgUrl => ImageHelper.NormalizeImageUrl(Picture);

        // Vrací true, pokud soubor existuje ve wwwroot
        public bool HasImage { get; set; }
        public string? QrBase64
        {
            get
            {
                if (PlateQr is null && Qr is null)
                {
                    return null;
                }
                using var gen = new QRCodeGenerator();
                using var qrData = gen.CreateQrCode((PlateQr is not null ? PlateQr : Qr)!, QRCodeGenerator.ECCLevel.M); // M/L/Q/H
                using var qrCode = new PngByteQRCode(qrData);
                return $"data:image/png;base64," + Convert.ToBase64String(qrCode.GetGraphic(10, false));
            }
        }
        public string? CssStyle 
        {
            get 
            {
                int size = 18;
                List<string> symbols = new List<string>();
                if (Frost) symbols.Add("frost");
                if (Surface is not null)
                {
                    foreach (char c in Surface)
                    {
                        symbols.Add(c.ToString().ToLower());
                    }

                }
                if (Antislip is not null) symbols.Add(Antislip.ToString().ToLower().Replace("/", ""));
                if (Abrasion is not null) symbols.Add(Abrasion.ToString().ToLower());
                if (Rectification) symbols.Add("rectification");

                if (symbols.Count == 0)
                    return string.Empty;

               symbols = symbols.AsEnumerable().Reverse().ToList();

                var images = symbols
                    .Select(s => $"url('/css/symbols/{s}.jpg')")
                    .ToArray();

                var positions = symbols
                    .Select((s, i) => $"right {8 + (i * (size - 1))}px top 5px")
                    .ToArray();

                return $"background-image: {string.Join(", ", images)}; " +
                       $"background-position: {string.Join(", ", positions)}; " +
                       $"background-size: {size}px {size}px; " +
                       $"background-repeat: no-repeat;";
            }
        }
        public string? StyleTagName
        {
            get
            {
                if (TagName is null) return "";

                string fontPath = Path.Combine(Directory.GetCurrentDirectory(), "wwwroot/fonts/OpenSans-ExtraBold.ttf");
                return $"font-size: {FontSizeHelper.GetFontSizeForOneLine(TagName.ToUpper(), fontPath, 24f, 360)}px;";
            }
        }

    }
}