using Jas.Models.Ptg;

namespace Jas.Application.Abstractions.Ptg
{
    public interface IStandDetailReader
    {
        Task<StandDetailData> GetAsync(int idStand, CancellationToken ct = default, DateTime? changeDate = null);
    }

    public sealed record StandDetailData(
        StandCompany Stand,
        List<Plate> Plates,
        List<PlateItem> Items,
        List<string>? ChangeTexts = null
    );
}
