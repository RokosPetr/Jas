<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Sessions;

use App\Core\Orm\BaseRepository;

class SessionRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Session::class];
    }
}

