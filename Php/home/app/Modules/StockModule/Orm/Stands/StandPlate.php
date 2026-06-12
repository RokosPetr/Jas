<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Stands;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\DeletableTrait;
use App\Modules\SystemModule\Orm\Files\File;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                          $id             {primary}
 * @property Stand                        $stand          {m:1 Stand::$plates}
 * @property int                          $order
 * @property string                       $description
 * @property string|null                  $dimension
 * @property File|null                    $picture        {m:1 File, oneSided=true}
 * @property string|null                  $qr
 * @property OneHasMany|StandPlateItem[]  $items          {1:m StandPlateItem::$plate, cascade=[persist, remove]}
 *
 * @property-read string|null             $orderType      {virtual}
 * @property-read int                     $state          {virtual}
 */
class StandPlate extends BaseEntity
{
    use DeletableTrait;

    public const STATE_ACTIVE = 1;
    public const STATE_PARTLY_ACTIVE = 2;
    public const STATE_INACTIVE = 3;

    public function getterOrderType(): ?string
    {
        if ($this->stand->type !== Stand::TYPE_PLATES || $this->stand->plateOrderType !== Stand::PLATE_ORDER_RIGHT_FIRST) {
            return null;
        }
        return $this->order % 2 ? 'P' : 'L';
    }

    public function getterState(): int
    {
        if ($this->items->toCollection()->findBy(['deleted' => false])->countStored() === 0) {
            return self::STATE_ACTIVE;
        }
        $inactiveCount = $this->items->toCollection()->findBy([
            'deleted' => false,
            'item->inactiveFrom!=' => null
        ])->countStored();
        if (!$inactiveCount) {
            return self::STATE_ACTIVE;
        }
        $activeCount = $this->items->toCollection()->findBy([
            'deleted' => false,
            'item->inactiveFrom' => null
        ])->countStored();
        return $activeCount ? self::STATE_PARTLY_ACTIVE : self::STATE_INACTIVE;
    }

    public function loadItems(): array
    {
        return $this->items->toCollection()->findBy(['deleted' => false])->orderBy('order')->fetchPairs('order');
    }
}
