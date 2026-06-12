<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\MainStorageOrders;

use App\Core\Orm\BaseRepository;

class MainStorageOrderItemRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [MainStorageOrderItem::class];
    }
}
