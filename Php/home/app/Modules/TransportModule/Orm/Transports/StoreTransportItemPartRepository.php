<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Transports;

use App\Core\Orm\BaseRepository;

class StoreTransportItemPartRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StoreTransportItemPart::class];
    }
}
