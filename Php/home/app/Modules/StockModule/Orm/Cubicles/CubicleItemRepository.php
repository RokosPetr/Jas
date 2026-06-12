<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Cubicles;

use App\Core\Orm\BaseRepository;

class CubicleItemRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [CubicleItem::class];
    }
}
