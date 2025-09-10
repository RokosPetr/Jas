using AutoMapper;
using Jas.Application.Abstractions.Ptg;
using Jas.Data.JasMtzDb;
using Jas.Models.Ptg;
using Microsoft.Data.SqlClient;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Caching.Memory;
using System.Data;

namespace Jas.Infrastructure.Ptg
{
    public sealed class StandDetailReader : IStandDetailReader
    {
        private readonly JasMtzDbContext _context;
        private readonly IMemoryCache _cache;
        private readonly IMapper _mapper;

        public StandDetailReader(JasMtzDbContext context, IMemoryCache cache, IMapper mapper)
        {
            _context = context;
            _cache = cache;
            _mapper = mapper;
        }

        public async Task<StandDetailData> GetAsync(int idStand, CancellationToken ct = default)
        {
            var cacheKey = $"stand:{idStand}:detail";
            if (_cache.TryGetValue(cacheKey, out StandDetailData data))
                return data;

            await using var conn = (SqlConnection)_context.Database.GetDbConnection();
            await conn.OpenAsync(ct);

            await using var cmd = new SqlCommand(@"
            EXEC dbo.sp_ptg_GetStandDetail @IdStand = @id;
        ", conn);
            cmd.Parameters.AddWithValue("@id", idStand);

            await using var reader = await cmd.ExecuteReaderAsync(ct);

            // 1) stand
            var stand = _mapper.Map<IDataReader, IEnumerable<StandCompany>>(reader).FirstOrDefault()
                        ?? throw new InvalidOperationException("Stand not found");

            // 2) plates
            await reader.NextResultAsync(ct);
            var plates = _mapper.Map<IDataReader, IEnumerable<Plate>>(reader).ToList();

            // 3) items
            await reader.NextResultAsync(ct);
            var items = _mapper.Map<IDataReader, IEnumerable<PlateItem>>(reader).ToList();

            // rychlý flag pro listingy; reálné ověření řešíš mimo tuto službu
            foreach (var it in items)
                it.HasImage = !string.IsNullOrWhiteSpace(it.ImgUrl);

            data = new StandDetailData(stand, plates, items);

            var opts = new MemoryCacheEntryOptions()
                .SetAbsoluteExpiration(TimeSpan.FromMinutes(10))
                .SetSlidingExpiration(TimeSpan.FromMinutes(3));

            _cache.Set(cacheKey, data, opts);
            return data;
        }
    }
}
