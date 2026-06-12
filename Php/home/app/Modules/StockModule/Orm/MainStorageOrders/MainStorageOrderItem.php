<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\MainStorageOrders;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbString;
use App\Modules\StockModule\Orm\StockItems\StockItem;

/**
 * @property int                          $id             {primary}
 * @property MainStorageOrder             $order          {m:1 MainStorageOrder::$items}
 * @property StockItem                    $item           {m:1 StockItem::$mainStorageOrders}
 * @property int                          $paletteCount
 * @property float                        $quantity
 * @property bool                         $stocked        {default false}
 *
 * @property-read string                  $regNumber      {virtual}
 * @property-read string                  $name           {virtual}
 * @property-read string                  $catalog        {virtual}
 * @property-read string                  $producer       {virtual}
 */
class MainStorageOrderItem extends BaseEntity
{
    public function getterRegNumber(): string
    {
        return $this->item->regNumber;
    }

    public function getterName(): string
    {
        return $this->item->name;
    }

    public function getterCatalog(): string
    {
        return $this->item->storageCatalog ?? '';
    }

    public function getterProducer(): string
    {
        return $this->item->producer->name ?? '';
    }

    public function getSqlRegNumber(): DbColumn
    {
        return new DbColumn('item->regNumber');
    }

    public function getSqlProducer(): DbColumn
    {
        return new DbColumn('item->producer->id');
    }

    public function getSqlName(): DbFunction
    {
        return new DbFunction(
            'CONCAT',
            new DbColumn('item->regNumber'),
            new DbString("' '"),
            new DbColumn('item->name')
        );
    }

    public function getExportQuantity(): string
    {
        return str_replace('.', ',', (string) $this->quantity);
    }
}
