<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Discounts;

use App\Core\Orm\BaseRepository;

class DiscountGroupRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [DiscountGroup::class];
    }
}
