<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\CustomGroups;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use App\Modules\StockModule\Orm\Producers\Producer;
use App\Modules\StockModule\Orm\StockGroups\StockGroup;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * @property int                                $id                    {primary}
 * @property string                             $name
 * @property int                                $viewType
 * @property ManyHasMany|StockGroup[]           $stockGroups           {m:m StockGroup::$customGroups, isMain=true}
 */
class CustomGroup extends BaseEntity
{
    public const VIEW_TYPE_TAKINGS_SUM = 1;
    public const VIEW_TYPE_STORE_TAKINGS = 2;

    use CreatableTrait;
    use UpdatableTrait;

    public function loadProducers(bool $withDcRavak = false): array
    {
        $producers = [];

        foreach ($this->stockGroups->toCollection()->orderBy('producer->number') as $stockGroup) {
            if (!isset($producers[$stockGroup->producer->id])) {
                $producers[$stockGroup->producer->id] = $stockGroup->producer->name;
            }

            if ($withDcRavak && $stockGroup->producer->name === Producer::RAVAK_NAME) {
                // DC Ravak hack
                $producers[Producer::DC_RAVAK_ID] = 'DC Ravak';
            }
        }

        return $producers;
    }
}
