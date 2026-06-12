<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Resources;

use App\Core\Orm\BaseMapper;

class ResourceMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'sys_resources';
}
