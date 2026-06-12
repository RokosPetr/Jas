<?php
declare(strict_types=1);

namespace App\Modules\MtzModule\Orm\MtzOrders;

use App\Core\Orm\BaseRepository;
use App\Modules\MtzModule\Orm\MtzItems\MtzItemRepository;

class MtzOrderRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [MtzOrder::class];
    }

    public function createOrder(array $basketItems, string $remark = null): ?MtzOrder
    {
        if (!$basketItems) {
            return null;
        }
        $mtzItemRepo = $this->getModel()->getRepository(MtzItemRepository::class);
        $order = new MtzOrder();
        $order->remark = $remark;

        foreach ($basketItems as $id => $quantity) {
            $orderItem = new MtzOrderItem();
            $orderItem->item = $mtzItemRepo->getById($id);
            $orderItem->quantity = $quantity;
            $order->items->add($orderItem);
        }

        $this->persistAndFlush($order);
        return $order;
    }
}
