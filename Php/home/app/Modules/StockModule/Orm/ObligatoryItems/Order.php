<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\ObligatoryItems;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\CreatableTrait;
use App\Modules\SystemModule\Orm\Stores\Store;

/**
 * @property int                     $id               {primary}
 * @property ObligatoryItem          $obligatoryItem   {m:1 ObligatoryItem::$orders}
 * @property Store                   $store            {m:1 Store, oneSided=true}
 * @property float                   $orderSum
 * @property float                   $preOrderQuantity
 */
class Order extends BaseEntity
{
    use CreatableTrait;
}
