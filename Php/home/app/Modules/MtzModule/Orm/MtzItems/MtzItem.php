<?php
declare(strict_types=1);

namespace App\Modules\MtzModule\Orm\MtzItems;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\DeletableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use App\Modules\MtzModule\Orm\MtzOrders\MtzOrderItem;
use App\Modules\SystemModule\Orm\Files\File;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                            $id               {primary}
 * @property int                            $regNumber
 * @property MtzGroup                       $group            {m:1 MtzGroup::$items}
 * @property string                         $name
 * @property string                         $description
 * @property string|null                    $remark
 * @property int|null                       $packageSize
 * @property int|null                       $packageUnit      {enum self::UNIT_*}
 * @property int|null                       $orderUnit        {enum self::UNIT_*}
 * @property File|null                      $picture          {m:1 File, oneSided=true}
 * @property OneHasMany|MtzOrderItem[]      $orders           {1:m MtzOrderItem::$item}
 *
 * @property-read string                    $title            {virtual}
 * @property-read string|null               $packageTitle     {virtual}
 * @property-read bool                      $hasPicture       {virtual}
 */
class MtzItem extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;
    use DeletableTrait;

    public const UNIT_PEACES = 1;
    public const UNIT_KILOS = 2;
    public const UNIT_LITERS = 3;
    public const UNIT_ROLLS = 4;
    public const UNIT_PACKAGES = 5;
    public const UNITS_LABELS = [
        self::UNIT_PEACES => 'ks',
        self::UNIT_KILOS => 'kg',
        self::UNIT_LITERS => 'l',
        self::UNIT_ROLLS => 'role',
        self::UNIT_PACKAGES => 'bal.'
    ];

    public function getterTitle(): string
    {
        return "$this->regNumber - $this->name";
    }

    public function getterPackageTitle(): ?string
    {
        if (!$this->packageSize) {
            return null;
        }
        if (!$this->packageUnit) {
            return "$this->packageSize";
        }
        return $this->packageUnit === self::UNIT_ROLLS && $this->packageSize > 4
            ? "$this->packageSize rolí"
            : "$this->packageSize " . self::UNITS_LABELS[$this->packageUnit];
    }

    public function getterHasPicture(): bool
    {
        return $this->picture !== null;
    }
}
