using Jas.Data.JasMtzDb;
using Jas.Helpers;
using QRCoder;
using System.Drawing;
using static System.Net.WebRequestMethods;
using static System.Runtime.InteropServices.JavaScript.JSType;

namespace Jas.Models.Ptg
{
    public class PlateItem
    {
        public int Id { get; set; }
        public int IdPtPlate { get; set; }
        public string RegNumber { get; set; }
        public string ItemName { get; set; }
        public int ItemOrder { get; set; }
        public bool SeriesItem { get; set; }

        // Z pdf_price_tag
        public string? TagName { get; set; }
        public string? Size { get; set; }
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
        public string? Picture => $"/images/www.koupelny-jas.cz/data/storage/images/product/{RegNumber}_1.jpg";

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
    }
}