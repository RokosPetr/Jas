<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Companies;

use App\Core\Orm\BaseRepository;

class GroupRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Group::class];
    }
}
