<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Users;

use App\Core\Orm\BaseMapper;

class UserMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'sys_users';

    /** DB vazebni tabulka */
    public string $table_sys_users_sys_roles = 'sys_users_roles';
}
