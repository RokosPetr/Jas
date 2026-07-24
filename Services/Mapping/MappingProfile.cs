using AutoMapper;
using Jas.Data.JasMtzDb;
using Jas.Models.Mtz;
using Jas.Models.Ptg;
using Jas.Models.Srv;
using Jas.Models.Jas;
using Jas.Services.Mapping.Resolvers;
using System;
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
            CreateMap<IDataRecord, PlateItem>()
                .ForMember(dest => dest.SeriesItem, opt => opt.MapFrom(src => GetBool(src, nameof(PlateItem.SeriesItem))))
                .ForMember(dest => dest.Frost, opt => opt.MapFrom(src => GetBool(src, nameof(PlateItem.Frost))))
                .ForMember(dest => dest.Rectification, opt => opt.MapFrom(src => GetBool(src, nameof(PlateItem.Rectification))))
                .ForMember(dest => dest.Outlet, opt => opt.MapFrom(src => GetBool(src, nameof(PlateItem.Outlet))))
                .ForMember(dest => dest.Discount, opt => opt.MapFrom(src => GetBool(src, nameof(PlateItem.Discount))))
                .ForMember(dest => dest.Discarded, opt => opt.MapFrom(src => GetBool(src, nameof(PlateItem.Discarded))))
                .ForMember(dest => dest.ToSellout, opt => opt.MapFrom(src => GetBool(src, nameof(PlateItem.ToSellout))));

            // kdyby DB vracela 0/1 místo bit:
            CreateMap<int, bool>().ConvertUsing(v => v != 0);
            CreateMap<int?, bool>().ConvertUsing(v => v.GetValueOrDefault() != 0);

            CreateMap<IDataRecord, SearchStandItem>();

            // SRV – mapování entita <-> view‑model
            CreateMap<SrvMaintenanceRequest, SrvMaintenanceRequestModel>()
                .ReverseMap();

            // Intranet - mapování entita DB <-> view-model
            CreateMap<Jas.Data.JasDb.GnReward, Jas.Models.Jas.GnReward>()
                .ForMember(dest => dest.RecordDate, opt => opt.MapFrom(src => src.RecordDate.ToDateTime(TimeOnly.MinValue)));
        }

        private static bool GetBool(IDataRecord record, string columnName)
        {
            var ordinal = GetOrdinal(record, columnName);
            if (ordinal < 0 || record.IsDBNull(ordinal))
            {
                return false;
            }

            var value = record.GetValue(ordinal);

            return value switch
            {
                bool b => b,
                byte bt => bt != 0,
                short s => s != 0,
                int i => i != 0,
                long l => l != 0,
                decimal d => d != 0,
                double db => Math.Abs(db) > double.Epsilon,
                float f => Math.Abs(f) > float.Epsilon,
                string str when bool.TryParse(str, out var parsedBool) => parsedBool,
                string str when decimal.TryParse(str, out var parsedDecimal) => parsedDecimal != 0,
                _ => Convert.ToBoolean(value)
            };
        }

        private static int GetOrdinal(IDataRecord record, string columnName)
        {
            for (var i = 0; i < record.FieldCount; i++)
            {
                if (string.Equals(record.GetName(i), columnName, StringComparison.OrdinalIgnoreCase))
                {
                    return i;
                }
            }

            return -1;
        }
    }
}
