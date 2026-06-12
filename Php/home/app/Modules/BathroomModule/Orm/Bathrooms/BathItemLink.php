<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Bathrooms;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbString;
use App\Modules\StockModule\Orm\StockItems\StockItem;

/**
 * @property int                       $id             {primary}
 * @property Bathroom                  $bathroom       {m:1 Bathroom::$itemLinks}
 * @property StockItem                 $item           {m:1 StockItem, oneSided=true}
 * @property string                    $link
 * @property float                     $positionX
 * @property float                     $positionY
 *
 * @property-read string               $position       {virtual}
 */
class BathItemLink extends BaseEntity
{
    public function getterPosition(): string
    {
        return "$this->positionX x $this->positionY";
    }

    public function getSqlItem(): DbFunction
    {
        return new DbFunction(
            'CONCAT',
            new DbColumn('item->regNumber'),
            new DbString("' '"),
            new DbColumn('item->name')
        );
    }

    public function getSiblingId(bool $prev = false): int
    {
        $itemLinkIds = $this->bathroom->itemLinks->toCollection()->orderBy('id')->fetchPairs(null, 'id');
        $itemLinkPosition = array_search($this->id, $itemLinkIds);
        $siblingPosition = $prev ? $itemLinkPosition - 1 : $itemLinkPosition + 1;
        if (isset($itemLinkIds[$siblingPosition])) {
            return $itemLinkIds[$siblingPosition];
        }
        return $siblingPosition < 0 ? end($itemLinkIds) : reset($itemLinkIds);
    }
}