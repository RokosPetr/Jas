<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Mails;

use App\Core\Orm\BaseRepository;

class MailRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Mail::class];
    }
}
