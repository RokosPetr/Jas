using AutoMapper;
using Jas.Data.JasIdentityDb;
using Jas.Data.JasMtzDb;
using Jas.Models.Mtz;

namespace Jas.Services.Mapping.Resolvers
{
    public class OrderUserResolver : IValueResolver<MtzOrder, Order, AspNetUser?>
    {
        private readonly IUserService _userCache;

        public OrderUserResolver(IUserService userCache)
        {
            _userCache = userCache;
        }

        public AspNetUser? Resolve(MtzOrder source, Order destination, AspNetUser? destMember, ResolutionContext context)
        {
            return source.IdUser != null ? _userCache.GetUser(source.IdUser) : null;
        }
    }
}
