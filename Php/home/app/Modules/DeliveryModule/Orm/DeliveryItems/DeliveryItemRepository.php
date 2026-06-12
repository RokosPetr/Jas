<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DeliveryItems;

use App\Core\Orm\BaseRepository;

class DeliveryItemRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [DeliveryItem::class];
    }
}
