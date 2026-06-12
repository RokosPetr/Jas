<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Discounts;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Modules\StockModule\Orm\Producers\Producer;
use App\Modules\StockModule\Orm\StockGroups\StockGroup;

/**
 * @property int                            $id               {primary}
 * @property StockGroup                     $stockGroup       {m:1 StockGroup, oneSided=true}
 * @property DiscountGroup                  $discountGroup    {m:1 DiscountGroup::$stockGroups}
 * @property float                          $value
 *
 * @property-read Producer                  $producer         {virtual}
 */
class DiscountStockGroup extends BaseEntity
{
    public function getterProducer(): Producer
    {
        return $this->stockGroup->producer;
    }

    public function getSqlProducer(): DbColumn
    {
        return new DbColumn('stockGroup->producer->id');
    }

    public function getSqlStockGroup(): DbColumn
    {
        return new DbColumn('stockGroup->name');
    }

    public function getSqlProducerNumber(): DbColumn
    {
        return new DbColumn('stockGroup->producer->number');
    }

    public function getSqlStockGroupNumber(): DbColumn
    {
        return new DbColumn('stockGroup->number');
    }
}