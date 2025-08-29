using AutoMapper;
using Jas.Data.JasMtzDb;
using Jas.Globals.Mtz;
using Jas.Models.Mtz;
using Jas.Services.Mtz;

namespace Jas.Services.Mapping.Resolvers
{
    public class OrderDepartmentResolver : IValueResolver<MtzOrder, Order, JasDepartment?>
    {
        private readonly IDepartmentService _Department;

        public OrderDepartmentResolver(IDepartmentService Department)
        {
            _Department = Department;
        }

        public JasDepartment? Resolve(MtzOrder source, Order destination, JasDepartment? destMember, ResolutionContext context)
        {
            return (destination.User != null && destination.User.DepartmentId != null) ? _Department.GetDepartment(destination.User.DepartmentId) : null;
        }
    }
}

