namespace Jas.Models.Ptg
{
    public class SearchStandItem
    {
        public string Label { get; set; } = string.Empty;
        public string Value { get; set; } = string.Empty;
        public string Type { get; set; } = string.Empty;
        public int Weight { get; set; }

        public int? IdStand { get; set; }
        public int? IdPlate { get; set; }
        public int? PlateCount { get; set; }
        public bool? PieceStand { get; set; }
    }
}
