using AutoMapper;
using Jas.Data.JasMtzDb;
using Jas.Models.Mtz;
using Jas.Services.Mtz;

namespace Jas.Services.Mapping.Resolvers
{
    public class OrderStoreResolver : IValueResolver<MtzOrder, Order, JasStore?>
    {
        private readonly IStoreService _store;

        public OrderStoreResolver(IStoreService store)
        {
            _store = store;
        }

        public JasStore? Resolve(MtzOrder source, Order destination, JasStore? destMember, ResolutionContext context)
        {
            return source.StoreId != null ? _store.GetStore(source.StoreId) : null;
        }
    }
}
