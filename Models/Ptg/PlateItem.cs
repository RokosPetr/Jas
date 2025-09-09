using Jas.Helpers;
using Jas.Data.JasMtzDb;

namespace Jas.Models.Ptg
{
    public class PlateItem
    {
        public int Id { get; set; }
        public int Id_Pt_Plate { get; set; }
        public string Reg_Number { get; set; }
        public string ItemName { get; set; }
        public int Item_Order { get; set; }
        public bool Series_Item { get; set; }

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

        // Obrázek (lokální cesta / relativní cesta k obrázku)
        public string? Picture => $"/images/koupelny-jas.cz/data/storage/images/product/{Reg_Number}_1.jpg";

        // Vrací upravenou URL (např. s CDN prefixem apod.)
        public string? ImgUrl => ImageHelper.NormalizeImageUrl(Picture);

        // Vrací true, pokud soubor existuje ve wwwroot
        public bool HasImage { get; set; }
    }
}