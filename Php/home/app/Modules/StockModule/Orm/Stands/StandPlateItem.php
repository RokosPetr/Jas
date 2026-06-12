<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Stands;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\DeletableTrait;
use App\Modules\StockModule\Orm\StockItems\StockItem;
use App\Modules\SystemModule\Orm\Files\File;

/**
 * @property int                          $id             {primary}
 * @property StandPlate                   $plate          {m:1 StandPlate::$items}
 * @property StockItem                    $item           {m:1 StockItem, oneSided=true}
 * @property int                          $order
 * @property bool                         $photoItem      {default false}
 * @property File|null                    $picture        {m:1 File, oneSided=true}
 * @property bool                         $seriesItem     {default false}
 *
 * @property-read bool                    $isActive       {virtual}
 * @property-read string|null             $inactiveFrom   {virtual}
 * @property-read string                  $itemTitle      {virtual}
 */
class StandPlateItem extends BaseEntity
{
    use DeletableTrait;

    public function getterIsActive(): bool
    {
        return $this->item->isActive;
    }

    public function getterInactiveFrom(): ?string
    {
        return $this->item->inactiveFrom
            ? $this->item->inactiveFrom->format('m/Y')
            : null;
    }

    public function getterItemTitle(): string
    {
        return $this->item->title;
    }
}
