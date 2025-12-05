using AutoMapper;
using Jas.Models.Ptg;
using Jas.Data.JasMtzDb;
using Microsoft.Data.SqlClient;
using Microsoft.EntityFrameworkCore;
using System.Data;

namespace Jas.Infrastructure.Ptg
{
    public interface IStandSearchReader
    {
        Task<List<SearchStandItem>> SearchAsync(string query, string? ico, int? voj, bool onlyMine, CancellationToken ct = default);
    }

    public sealed class StandSearchReader : IStandSearchReader
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;

        public StandSearchReader(JasMtzDbContext context, IMapper mapper)
        {
            _context = context;
            _mapper = mapper;
        }

        public async Task<List<SearchStandItem>> SearchAsync(string query, string? ico, int? voj, bool onlyMine, CancellationToken ct = default)
        {
            await using var conn = (SqlConnection)_context.Database.GetDbConnection();
            await conn.OpenAsync(ct);

            await using var cmd = new SqlCommand(
                "EXEC dbo.sp_ptg_search @SearchString = @q, @Ico = @ico, @Voj = @voj, @OnlyMine = @onlyMine",
                conn);

            cmd.Parameters.AddWithValue("@q", query);

            cmd.Parameters.AddWithValue("@ico", (object?)ico ?? DBNull.Value);
            cmd.Parameters.AddWithValue("@voj", (object?)voj ?? DBNull.Value);
            cmd.Parameters.AddWithValue("@onlyMine", onlyMine);
            
            await using var reader = await cmd.ExecuteReaderAsync(ct);

            // SP vrací jediný výsledek → můžeme rovnou namapovat
            var items = _mapper
                .Map<IDataReader, IEnumerable<SearchStandItem>>(reader)
                .ToList();

            return items;
        }
    }
}
