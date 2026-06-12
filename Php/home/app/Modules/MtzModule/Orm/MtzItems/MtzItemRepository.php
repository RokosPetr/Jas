<?php
declare(strict_types=1);

namespace App\Modules\MtzModule\Orm\MtzItems;

use App\Core\Orm\BaseRepository;

/**
 * @method array loadMtzTree()
 */

class MtzItemRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [MtzItem::class];
    }

    public const IMAGE_DIR = '/www/upload/mtzItems';
}
