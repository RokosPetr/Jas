<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Resources;

use App\Core\Orm\BaseRepository;

class ResourceRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Resource::class];
    }
}
