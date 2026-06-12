<?php
declare(strict_types=1);

namespace App\Modules\MtzModule\Orm\MtzOrders;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\CreatableTrait;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                            $id               {primary}
 * @property string|null                    $remark
 * @property OneHasMany|MtzOrderItem[]      $items            {1:m MtzOrderItem::$order}
 */
class MtzOrder extends BaseEntity
{
    use CreatableTrait;
}
