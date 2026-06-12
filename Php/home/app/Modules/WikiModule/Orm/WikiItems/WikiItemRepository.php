<?php
declare(strict_types=1);

namespace App\Modules\WikiModule\Orm\WikiItems;

use App\Core\Orm\BaseRepository;

class WikiItemRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [WikiItem::class];
    }
}