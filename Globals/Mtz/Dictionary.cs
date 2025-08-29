using Jas.Data.JasMtzDb;
using Jas.Models.Mtz;
using Microsoft.AspNetCore.Mvc.Rendering;
using ENUMS = Jas.Globals.Mtz.Enums;

namespace Jas.Globals.Mtz
{
    public static class Stores
    {
        private static Dictionary<int, string> _storeDictionary
        {
            get
            {
                Dictionary<int, string> storeDictionary = new Dictionary<int, string>()
                {
                    {1, "Prodejna Šumperk"},
                    {2, "Prodejna Olomouc"},
                    {3, "Prodejna Otrokovice"},
                    {4, "Prodejna Ostrava"},
                    {5, "Prodejna Prostějov"},
                    {6, "Prodejna Teplice"},
                    {7, "Prodejna Valašské Meziříčí"},
                    {8, "Prodejna Hradec Králové"},
                    {9, "Velkoobchod"},
                    {10, "Hlučín"},
                    {11, "LC Hrabová"}
                };

                return storeDictionary;
            }
        }

        public static string StoreName(int? StoreId)
        {
            if (StoreId == null)
            {
                return null;
            }
            return _storeDictionary[(int)StoreId];
        }
    }

    public static class OrderStates
    {
        private static Dictionary<int, string> _orderStateDictionary
        {
            get
            {
                Dictionary<int, string> storeDictionary = new Dictionary<int, string>()
                {
                    // user/admin
                    { (int)ENUMS.OrderStates.InCart, "Košík" },
                    { (int)ENUMS.OrderStates.Received, "Odeslaná;Přijatá" },
                    { (int)ENUMS.OrderStates.InProgress, "Rozpracovaná" },
                    { (int)ENUMS.OrderStates.ToSend, "K odeslání" },
                    { (int)ENUMS.OrderStates.Processed, "Vyřízená" },
                    { (int)ENUMS.OrderStates.Cancelled, "Stornovaná" },
                    { (int)ENUMS.OrderStates.ProcessedCancelled, "Vyřízená (storno)" }
                };

                return storeDictionary;
            }
        }

        public static Dictionary<int, int> StateGroup
        {
            get
            {
                Dictionary<int, int> value = new Dictionary<int, int>();
                value.Add((int)ENUMS.OrderStates.Received, (int)ENUMS.OrderStates.Received);
                value.Add((int)ENUMS.OrderStates.InProgress, (int)ENUMS.OrderStates.InProgress);
                value.Add((int)ENUMS.OrderStates.Processed, (int)ENUMS.OrderStates.Processed);
                value.Add((int)ENUMS.OrderStates.ProcessedCancelled, (int)ENUMS.OrderStates.Processed);
                value.Add((int)ENUMS.OrderStates.Cancelled, (int)ENUMS.OrderStates.Processed);
                return value;
            }
        }

        public static string OrderStateLabel(int? State, bool IsAdmin)
        {
            if (State == null)
            {
                return null;
            }
            string[] labels = _orderStateDictionary[(int)State].Split(';');
            if (labels.Count() > 1)
            {
                if (IsAdmin)
                {
                    return labels[1];
                }
                else
                {
                    return labels[0];
                }
            }

            return _orderStateDictionary[(int)State];
        }

        public static SelectList OrderItemStateSelectList(int? state)
        {
            SelectList selectList = new SelectList(
                new List<SelectListItem>
                {
            new SelectListItem {Text = "- (nová)", Value = ((int)ENUMS.OrderStates.Received).ToString(), Disabled = true},
            new SelectListItem {Text = "O (objednáno)", Value = ((int)ENUMS.OrderStates.Ordered).ToString()},
            new SelectListItem {Text = "X (vyřízeno)", Value = ((int)ENUMS.OrderStates.Processed).ToString()},
            new SelectListItem {Text = "S (stornováno)", Value = ((int)ENUMS.OrderStates.Cancelled).ToString()}
                }, "Value", "Text", state);
            return selectList;
        }
        public static string OrderItemStateLabel(int? State)
        {
            SelectList selectList = OrderItemStateSelectList(null);
            return selectList.First(i => int.Parse(i.Value) == (int)State).Text;
        }
    }

    public static class UserOrders
    {
        public static Dictionary<int, int?> GetItemsCount(List<Order> mtzOrders, bool isAdmin, string userId)
        {
            Dictionary<int, int?> OrdersItemsCount = new Dictionary<int, int?>();
            foreach (var item in mtzOrders.Where(i => i.State > (int)ENUMS.OrderStates.InCart).Select(t => new { t.State }).GroupBy(g => g.State).ToDictionary(t => t.Key, t => t.Count()))
            {
                OrdersItemsCount.Add(item.Key, item.Value);
            }

            MtzOrder mtzOrder;
            int notDeliveredCount = 0;
            if (isAdmin)
            {
                notDeliveredCount = mtzOrders.Select(e => new { count = e.MtzOrderItems.Count(i => i.State == (int)ENUMS.OrderStates.Received || i.State == (int)ENUMS.OrderStates.Ordered) }).Sum(i => i.count);
                mtzOrder = mtzOrders.FirstOrDefault(i => i.State == (int)ENUMS.OrderStates.InCart && i.IdUser == userId);
            }
            else
            {
                mtzOrder = mtzOrders.FirstOrDefault(i => i.State == (int)ENUMS.OrderStates.InCart);
            }
            if (mtzOrder != null)
            {
                OrdersItemsCount.Add(0, mtzOrder.MtzOrderItems.Count);
            }

            for (int i = 0; i < 8; i++)
            {
                if (!OrdersItemsCount.ContainsKey(i))
                {
                    OrdersItemsCount.Add(i, i == 0 ? 0 : null);
                }
            }

            OrdersItemsCount.Add(-1, notDeliveredCount == 0 ? null : notDeliveredCount);

            return OrdersItemsCount;
        }
    }

}
