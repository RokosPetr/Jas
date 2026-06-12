<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Cubicles;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbString;
use App\Modules\StockModule\Orm\StockItems\StockItem;

/**
 * @property int                          $id               {primary}
 * @property Cubicle                      $cubicle          {m:1 Cubicle::$items}
 * @property StockItem                    $item             {m:1 StockItem, oneSided=true}
 * @property float                        $quantity
 *
 * @property-read string                  $unit             {virtual}
 * @property-read float                   $price            {virtual}
 */
class CubicleItem extends BaseEntity
{
    public function getterUnit(): string
    {
        return $this->item->unit->name ?? '-';
    }

    public function getterPrice(): float
    {
        return $this->item->price ?? 0;
    }

    public function getSqlItem(): DbFunction
    {
        return new DbFunction(
            'CONCAT',
            new DbColumn('item->regNumber'),
            new DbString("'-'"),
            new DbColumn('item->name')
        );
    }
}
