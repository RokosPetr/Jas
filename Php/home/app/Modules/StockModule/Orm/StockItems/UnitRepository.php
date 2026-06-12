<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockItems;

use App\Core\Orm\BaseRepository;

class UnitRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Unit::class];
    }
}
