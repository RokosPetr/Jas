<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockGroups;

use App\Core\Orm\BaseRepository;
use App\Modules\StockModule\Orm\Producers\Producer;
use Nextras\Orm\Collection\ICollection;

class StockGroupRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StockGroup::class];
    }

    public function findDcRavakGroups(): ICollection
    {
        return $this->findBy([
            'producer->name' => Producer::RAVAK_NAME,
            'number' => Producer::DC_RAVAK_GROUPS
        ]);
    }
}
