<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockSectors;

use App\Core\Orm\BaseRepository;

class StockSectorRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StockSector::class];
    }
}
