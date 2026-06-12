<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Drivers;

use App\Core\Orm\BaseRepository;

class StoreDriverRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StoreDriver::class];
    }
}
