<?php
declare(strict_types=1);

namespace App\Modules\MtzModule\Orm\MtzOrders;

use App\Core\Orm\BaseRepository;

class MtzOrderItemRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [MtzOrderItem::class];
    }
}
