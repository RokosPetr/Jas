<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Phones;

use App\Core\Orm\BaseRepository;

class PhoneRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Phone::class];
    }
}
