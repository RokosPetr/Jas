<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DepotStandRelocations;

use App\Core\Orm\BaseRepository;

class DepotStandRelocationRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [DepotStandRelocation::class];
    }
}
