using Jas.Data.JasMtzDb;
using Microsoft.EntityFrameworkCore;

namespace Jas.Services.Mtz
{
    public interface IStoreService
    {
        Task InitializeAsync();
        JasStore? GetStore(int? storeId);
    }

    public class StoreService : IStoreService
    {
        private readonly JasMtzDbContext _dbContext;
        private Dictionary<int, JasStore> _stores;

        public StoreService(JasMtzDbContext dbContext)
        {
            _dbContext = dbContext;
        }

        public async Task InitializeAsync()
        {
            if (_stores is null)
            {
                var stores = await _dbContext.JasStores.ToListAsync();
                _stores = stores.ToDictionary(u => u.Id, u => u);
            }
        }

        public JasStore? GetStore(int? storeId)
        {

            if (storeId is null || _stores is null) return null;

            return _stores.TryGetValue((int)storeId, out var store) ? store : null;
        }
    }

}
