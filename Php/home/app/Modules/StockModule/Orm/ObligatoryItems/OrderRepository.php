<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\ObligatoryItems;

use App\Core\Orm\BaseRepository;

class OrderRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Order::class];
    }
}
