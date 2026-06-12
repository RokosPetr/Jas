<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Discounts;

use App\Core\Orm\BaseEntity;
use App\Modules\DeliveryModule\Orm\Companies\Depot;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                                $id               {primary}
 * @property int                                $number
 * @property string|null                        $name
 *
 * @property OneHasMany|DiscountStockItem[]     $stockItems       {1:m DiscountStockItem::$discountGroup}
 * @property OneHasMany|DiscountStockGroup[]    $stockGroups      {1:m DiscountStockGroup::$discountGroup}
 * @property OneHasMany|Depot[]                 $depots           {1:m Depot::$discountGroup}
 *
 * @property-read string                        $title            {virtual}
 * @property-read int                           $stockItemCount   {virtual}
 * @property-read int                           $stockGroupCount  {virtual}
 * @property-read int                           $depotCount       {virtual}
 */
class DiscountGroup extends BaseEntity
{
    public function getterTitle(): string
    {
        $title = "$this->number";
        if ($this->name) {
            $title .= " ($this->name)";
        }
        return $title;
    }

    public function getterStockItemCount(): int
    {
        return $this->stockItems->countStored();
    }

    public function getterStockGroupCount(): int
    {
        return $this->stockGroups->countStored();
    }

    public function getterDepotCount(): int
    {
        return $this->depots->countStored();
    }
}