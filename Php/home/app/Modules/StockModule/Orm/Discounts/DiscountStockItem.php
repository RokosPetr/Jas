<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Discounts;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbString;
use App\Modules\StockModule\Orm\Producers\Producer;
use App\Modules\StockModule\Orm\StockGroups\StockGroup;
use App\Modules\StockModule\Orm\StockItems\StockItem;

/**
 * @property int                            $id               {primary}
 * @property StockItem                      $stockItem        {m:1 StockItem, oneSided=true}
 * @property DiscountGroup                  $discountGroup    {m:1 DiscountGroup::$stockItems}
 * @property float                          $value
 *
 * @property-read Producer                  $producer         {virtual}
 * @property-read StockGroup                $stockGroup       {virtual}
 */
class DiscountStockItem extends BaseEntity
{
    public function getterProducer(): Producer
    {
        return $this->stockItem->producer;
    }

    public function getterStockGroup(): StockGroup
    {
        return $this->stockItem->group;
    }

    public function getSqlStockItem(): DbFunction
    {
        return new DbFunction(
            'CONCAT',
            new DbColumn('stockItem->regNumber'),
            new DbString("' '"),
            new DbColumn('stockItem->name')
        );
    }

    public function getSqlProducerNumber(): DbColumn
    {
        return new DbColumn('stockItem->group->producer->number');
    }

    public function getSqlStockItemNumber(): DbColumn
    {
        return new DbColumn('stockItem->regNumber');
    }
}