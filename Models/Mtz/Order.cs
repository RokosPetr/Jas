using Jas.Data.JasIdentityDb;
using Jas.Data.JasMtzDb;

namespace Jas.Models.Mtz
{
    public class Order : MtzOrder
    {
        public JasMtzDbContext? DbContext { get; set; }
        public List<OrderItem>? OrderItems { get; set; }
        public string StoreAndDepartmentName
        {
            get
            {
                if (Store != null) return string.Format("{0} - {1}", Store?.Name ?? "", Department?.Name ?? "");
                return null;
            }
        }
        public string Address
        {
            get
            {
                if (StoreAndDepartmentName != null) return string.Format("{0}\n{1}\n{2}", StoreAndDepartmentName, User.Name, string.Join("\n", Department.Address.Split(',')));
                return null;
            }
        }
        public string AddressHtml
        {
            get
            {
                if(Address != null) return Address.Replace("\n", "<br/>");
                return null;
            }
        }
        public AspNetUser? User { get; set; }
        public JasStore? Store { get; set; }
        public JasDepartment? Department { get; set; }
    }
}
