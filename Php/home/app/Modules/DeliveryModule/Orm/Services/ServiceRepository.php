<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Services;

use App\Core\Orm\BaseRepository;

class ServiceRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Service::class];
    }
}
