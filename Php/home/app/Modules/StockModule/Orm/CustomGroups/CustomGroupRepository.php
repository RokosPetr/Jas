<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\CustomGroups;

use App\Core\Orm\BaseRepository;

class CustomGroupRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [CustomGroup::class];
    }
}
