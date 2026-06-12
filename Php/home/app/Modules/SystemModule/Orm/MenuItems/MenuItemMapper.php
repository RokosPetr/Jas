<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\MenuItems;

use App\Core\Orm\BaseMapper;

class MenuItemMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'sys_menu_items';
}
