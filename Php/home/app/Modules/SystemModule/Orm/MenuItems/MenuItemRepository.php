<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\MenuItems;

use App\Core\Orm\BaseRepository;

class MenuItemRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [MenuItem::class];
    }
}
