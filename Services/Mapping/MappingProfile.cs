using AutoMapper;
using Jas.Data.JasMtzDb;
using Jas.Models.Mtz;
using Jas.Models.Ptg;
using Jas.Services.Mapping.Resolvers;
using System.Data;

namespace Jas.Services.Mapping
{
    public class MappingProfile : Profile
    {
        public MappingProfile()
        {
            CreateMap<MtzProduct, Product>();
            CreateMap<Product, MtzProduct>();
            CreateMap<MtzOrderItem, OrderItem>()
                .ForMember(dest => dest.Product, opt => opt.MapFrom(src => src.IdProductNavigation))
                .ForMember(dest => dest.Order, opt => opt.MapFrom(src => src.IdOrderNavigation));
            CreateMap<MtzOrder, Order>()
                .ForMember(dest => dest.User, opt => opt.MapFrom<OrderUserResolver>())
                .ForMember(dest => dest.Department, opt => opt.MapFrom<OrderDepartmentResolver>())
                .ForMember(dest => dest.Store, opt => opt.MapFrom<OrderStoreResolver>())
                .ForMember(dest => dest.OrderItems, opt => opt.MapFrom(src => src.MtzOrderItems));
            CreateMap<ViPtgStandCompany, StandCompany>();

            CreateMap<IDataRecord, StandCompany>();
            CreateMap<IDataRecord, Plate>();
            CreateMap<IDataRecord, PlateItem>();

            // kdyby DB vracela 0/1 místo bit:
            CreateMap<int, bool>().ConvertUsing(v => v != 0);
            CreateMap<int?, bool>().ConvertUsing(v => v.GetValueOrDefault() != 0);

            CreateMap<IDataRecord, SearchStandItem>();
        }
    }
}
