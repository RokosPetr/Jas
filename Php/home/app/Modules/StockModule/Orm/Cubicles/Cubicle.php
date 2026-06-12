<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Cubicles;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbString;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\DeletableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use App\Modules\DeliveryModule\Orm\Companies\Depot;
use App\Modules\SystemModule\Orm\Files\File;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                          $id               {primary}
 * @property Depot                        $depot            {m:1 Depot, oneSided=true}
 * @property int                          $codeFirstPart
 * @property int                          $codeSecondPart
 * @property string                       $name
 * @property int                          $month
 * @property int                          $year
 * @property float                        $size
 * @property File|null                    $picture          {m:1 File, oneSided=true}
 * @property bool                         $isRival
 * @property string|null                  $remark
 * @property int                          $tag              {enum self::TAG_*}
 * @property OneHasMany|CubicleItem[]     $items            {1:m CubicleItem::$cubicle}
 *
 * @property-read int                     $state            {virtual}
 * @property-read string                  $depotName        {virtual}
 * @property-read string                  $code             {virtual}
 * @property-read string                  $date             {virtual}
 * @property-read string                  $title            {virtual}
 * @property-read int                     $activityPeriod   {virtual}
 * @property-read int                     $itemCount        {virtual}
 * @property-read array                   $producers        {virtual}
 */
class Cubicle extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;
    use DeletableTrait;

    public const STATE_ACTIVE = 1;
    public const STATE_PARTLY_ACTIVE = 2;
    public const STATE_INACTIVE = 3;

    public const TAG_TO_BUILD_UP = 1;
    public const TAG_CURRENT = 2;
    public const TAG_TO_GLUE_OVER = 3;
    public const TAGS_LABELS = [
        self::TAG_TO_BUILD_UP => 'Nově k vylepení',
        self::TAG_CURRENT => 'Aktuální',
        self::TAG_TO_GLUE_OVER => 'Přelepit'
    ];

    public function getterCode(): string
    {
        return "$this->codeFirstPart/$this->codeSecondPart";
    }

    public function getterDate(): string
    {
        $month = str_pad((string) $this->month, 2, '0', STR_PAD_LEFT);
        return "$month/$this->year";
    }

    public function getterActivityPeriod(): int
    {
        $periodStart = (new DateTimeImmutable())->setDate($this->year, $this->month, 1);
        $periodEnd = $this->deletedAt ?: new DateTimeImmutable();
        $interval = $periodEnd->diff($periodStart);
        return 12 * $interval->y + $interval->m;
    }

    public function getSqlCode(): DbFunction
    {
        return new DbFunction(
            'CONCAT',
            new DbColumn('codeFirstPart'),
            new DbString("'/'"),
            new DbColumn('codeSecondPart')
        );
    }

    public function getterTitle(): string
    {
        return "$this->code - $this->name";
    }

    public function getterItemCount(): int
    {
        return $this->items->countStored();
    }

    public function getterProducers(): array
    {
        return array_unique(
            $this->items->toCollection()
                ->orderBy('item->producer->number')
                ->fetchPairs(null, 'item->producer->name')
        );
    }

    public function getSqlProducers(): DbColumn
    {
        return new DbColumn('items->item->producer->id');
    }

    public function getterDepotName(): string
    {
        return $this->depot->name;
    }

    public function getSqlDepotName(): DbFunction
    {
        return new DbFunction(
            'CONCAT_WS',
            new DbColumn('depot->company->name'),
            new DbString("' '"),
            new DbColumn('depot->title')
        );
    }

    public function getterState(): int
    {
        if ($this->items->countStored() === 0) {
            return self::STATE_ACTIVE;
        }
        $inactiveCount = $this->items->toCollection()->findBy(['item->inactiveFrom!=' => null])->countStored();
        if (!$inactiveCount) {
            return self::STATE_ACTIVE;
        }
        $activeCount = $this->items->toCollection()->findBy(['item->inactiveFrom' => null])->countStored();
        return $activeCount ? self::STATE_PARTLY_ACTIVE : self::STATE_INACTIVE;
    }
}
