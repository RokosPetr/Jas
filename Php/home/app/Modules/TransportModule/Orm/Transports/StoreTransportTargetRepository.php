<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Transports;

use App\Core\Orm\BaseRepository;

class StoreTransportTargetRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StoreTransportTarget::class];
    }
}
