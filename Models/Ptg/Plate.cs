using Jas.Helpers;
using Jas.Data.JasMtzDb;

namespace Jas.Models.Ptg
{
    public class Plate
    {
        public int IdPlate { get; set; }
        public int IdStand { get; set; }
        public string? Description { get; set; }
        public string? Qr { get; set; }
        public int PlateOrder { get; set; }
        public string? Picture { get; set; }
        public string? ImgUrl => ImageHelper.NormalizeImageUrl(Picture);
        public int ProductGroupCount { get; set; }
        public int RegNumberCount { get; set; }
    }
}
