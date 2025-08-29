using Jas.Data.JasMtzDb;
using Jas.Models.Mtz;

namespace Jas.Models.Mtz
{
    public class OrderItem : MtzOrderItem
    {
        public int? IdOrderItem { get; set; }
        public Product Product { get; set; }
        public Order Order { get; set; }
        public int AmountUpdate { get; set; } = 0;
        public string StoreName { get; set; }
        public string UserName
        {
            get 
            {
                if (Order is null) return "";
                return string.Format("<b>{0}</b><br/>{1}", Order?.StoreAndDepartmentName ?? "", Order.User?.Name ?? "");
            }
        }
        public string? ProductCode {
            get
            {
                if (Product is null) return null;
                if (Product.MtzProductAttributes.Count > 0)
                {
                    MtzProductAttribute? productAttribut = Product.MtzProductAttributes.FirstOrDefault(f => f.Value.Equals(SelectedSize) && f.ProductCode is not null);
                    if (productAttribut is not null)
                    {
                        return productAttribut.ProductCode;
                    }
                }
                return Product.Code;
            }
        }

    }

}
