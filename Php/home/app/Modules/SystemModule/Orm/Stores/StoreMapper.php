<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Stores;

use App\Core\Orm\BaseMapper;

class StoreMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'sys_stores';
}
