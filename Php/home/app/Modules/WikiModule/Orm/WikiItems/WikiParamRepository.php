<?php
declare(strict_types=1);

namespace App\Modules\WikiModule\Orm\WikiItems;

use App\Core\Orm\BaseRepository;
use Nextras\Orm\Collection\ICollection;

class WikiParamRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [WikiParam::class];
    }

    public function getNextOrder(int $itemId): int
    {
        return ($this->findBy(['item->id' => $itemId])->orderBy('order', ICollection::DESC)
            ->fetch()->order ?? 0) + 1;
    }
}