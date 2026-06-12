<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\StoreSettings;

use App\Core\Orm\BaseMapper;

class StoreSettingMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'sys_store_settings';
}
