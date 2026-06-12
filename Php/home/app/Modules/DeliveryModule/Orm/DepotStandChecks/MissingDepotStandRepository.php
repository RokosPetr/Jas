<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DepotStandChecks;

use App\Core\Orm\BaseRepository;

class MissingDepotStandRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [MissingDepotStand::class];
    }
}
