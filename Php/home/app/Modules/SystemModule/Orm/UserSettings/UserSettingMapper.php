<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\UserSettings;

use App\Core\Orm\BaseConventions;
use App\Core\Orm\BaseMapper;

class UserSettingMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'sys_user_settings';

    /** JSON column definition */
    protected function createConventions() : BaseConventions
    {
        $conventions = parent::createConventions();
        $conventions->addMapping(
            'setting',
            'setting',
            static fn($val) =>  json_decode($val ?? '[]', true),
            static fn($val) =>  json_encode($val ?? [])
        );

        return $conventions;
    }
}
