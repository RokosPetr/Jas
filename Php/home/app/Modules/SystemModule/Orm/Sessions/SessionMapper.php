<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Sessions;

use App\Core\Orm\BaseMapper;

class SessionMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'sys_sessions';
}
