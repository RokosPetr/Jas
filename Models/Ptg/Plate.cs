using Jas.Helpers;
using Jas.Data.JasMtzDb;

namespace Jas.Models.Ptg
{
    public class Plate
    {
        public int Id { get; set; }
        public int Id_Pt_Stand { get; set; }
        public string? Description { get; set; }
        public string? Qr { get; set; }
        public int Plate_Order { get; set; }
        public string? Picture { get; set; }
        public string? ImgUrl => ImageHelper.NormalizeImageUrl(Picture);
    }
}
