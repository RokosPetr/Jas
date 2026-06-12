<?php
declare(strict_types=1);

namespace App\Modules\MtzModule\Orm\MtzOrders;

use App\Core\Orm\BaseEntity;
use App\Modules\MtzModule\Orm\MtzItems\MtzItem;

/**
 * @property int                            $id               {primary}
 * @property MtzOrder                       $order            {m:1 MtzOrder::$items}
 * @property MtzItem                        $item             {m:1 MtzItem::$orders}
 * @property int                            $quantity
 */
class MtzOrderItem extends BaseEntity
{

}
