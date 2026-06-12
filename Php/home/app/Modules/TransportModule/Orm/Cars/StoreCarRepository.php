<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Cars;

use App\Core\Orm\BaseRepository;

class StoreCarRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StoreCar::class];
    }
}
