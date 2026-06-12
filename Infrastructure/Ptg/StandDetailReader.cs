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

        public async Task<StandDetailData> GetAsync(int idStand, CancellationToken ct = default, DateTime? changeDate = null)
        {
            var cacheKey = $"stand:{idStand}:detail:{changeDate:O}";
            if (_cache.TryGetValue(cacheKey, out StandDetailData? data))
                return data;

            await using var conn = (SqlConnection)_context.Database.GetDbConnection();
            await conn.OpenAsync(ct);

            var sql = changeDate.HasValue
                ? @"
            EXEC dbo.sp_ptg_GetStandDetailChange @IdStand = @id, @ChangeDate = @changeDate;
        "
                : @"
            EXEC dbo.sp_ptg_GetStandDetail @IdStand = @id;
        ";

            await using var cmd = new SqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@id", idStand);
            if (changeDate.HasValue)
            {
                cmd.Parameters.Add("@changeDate", SqlDbType.DateTime).Value = changeDate.Value;
            }

            await using var reader = await cmd.ExecuteReaderAsync(ct);

            // 1) stand
            var stand = _mapper.Map<IDataReader, IEnumerable<StandCompany>>(reader).FirstOrDefault()
                        ?? throw new InvalidOperationException("Stand not found");

            // 2) plates
            await reader.NextResultAsync(ct);
            var plates = _mapper.Map<IDataReader, IEnumerable<Plate>>(reader).ToList();

            var changeTexts = new List<string>();
            var items = new List<PlateItem>();

            while (await reader.NextResultAsync(ct))
            {
                if (HasColumn(reader, "ChangeText"))
                {
                    var changeTextOrdinal = reader.GetOrdinal("ChangeText");
                    while (await reader.ReadAsync(ct))
                    {
                        if (!reader.IsDBNull(changeTextOrdinal))
                        {
                            changeTexts.Add(reader.GetString(changeTextOrdinal));
                        }
                    }
                }
                else
                {
                    items = _mapper.Map<IDataReader, IEnumerable<PlateItem>>(reader).ToList();
                }
            }

            // rychlý flag pro listingy; reálné ověření řešíš mimo tuto službu
            foreach (var it in items)
                it.HasImage = !string.IsNullOrWhiteSpace(it.ImgUrl);

            data = new StandDetailData(stand, plates, items, changeTexts);

            var opts = new MemoryCacheEntryOptions()
                .SetAbsoluteExpiration(TimeSpan.FromMinutes(10))
                .SetSlidingExpiration(TimeSpan.FromMinutes(3));

            _cache.Set(cacheKey, data, opts);
            return data;
        }

        private static bool HasColumn(IDataRecord record, string columnName)
        {
            for (var i = 0; i < record.FieldCount; i++)
            {
                if (string.Equals(record.GetName(i), columnName, StringComparison.OrdinalIgnoreCase))
                {
                    return true;
                }
            }

            return false;
        }
    }
}
