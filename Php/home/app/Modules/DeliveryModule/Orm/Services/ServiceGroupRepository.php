<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Services;

use App\Core\Orm\BaseRepository;

class ServiceGroupRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [ServiceGroup::class];
    }
}
