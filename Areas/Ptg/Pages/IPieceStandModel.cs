using Jas.Models.Ptg;

namespace Jas.Areas.Ptg.Pages
{
    public interface IPieceStandModel
    {
        List<PlateItem> PlateItems { get; }
        bool PrintQr { get; }
        bool VoPrice { get; }
        IReadOnlyList<string> ChangeTexts { get; }
    }
}
