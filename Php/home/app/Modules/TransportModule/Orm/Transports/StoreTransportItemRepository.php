<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Transports;

use App\Core\Orm\BaseRepository;

class StoreTransportItemRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StoreTransportItem::class];
    }
}
