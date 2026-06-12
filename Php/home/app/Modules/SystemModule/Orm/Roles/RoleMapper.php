<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Roles;

use App\Core\Orm\BaseMapper;

class RoleMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'sys_roles';

    /** DB vazebni tabulka */
    public string $table_sys_roles_sys_resources = 'sys_roles_resources';
}
