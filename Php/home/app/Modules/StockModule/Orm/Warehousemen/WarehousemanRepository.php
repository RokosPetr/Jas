<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Warehousemen;

use App\Core\Orm\BaseRepository;

class WarehousemanRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Warehouseman::class];
    }
}
