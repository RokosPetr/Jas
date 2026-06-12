<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\UserSettings;

use App\Core\Orm\BaseRepository;

class UserSettingRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [UserSetting::class];
    }
}
