<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Roles;

use App\Core\Orm\BaseRepository;

class RoleRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Role::class];
    }
}
