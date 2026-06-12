<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Stands;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\DeletableTrait;
use App\Modules\StockModule\Orm\Producers\Producer;
use App\Modules\SystemModule\Orm\Files\File;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                          $id             {primary}
 * @property string                       $code
 * @property int                          $codeFirstPart
 * @property int                          $codeSecondPart
 * @property string                       $name
 * @property int                          $year
 * @property Producer|null                $producer       {m:1 Producer, oneSided=true}
 * @property int                          $type           {enum self::TYPE_*}
 * @property int|null                     $plateOrderType {enum self::PLATE_ORDER_*}
 * @property float                        $width
 * @property float                        $depth
 * @property float                        $height
 * @property int                          $unitCount
 * @property File|null                    $picture        {m:1 File, oneSided=true}
 * @property File|null                    $secondPicture  {m:1 File, oneSided=true}
 * @property OneHasMany|StandNote[]       $standNotes     {1:m StandNote::$stand}
 * @property OneHasMany|StandPlate[]      $plates         {1:m StandPlate::$stand, cascade=[persist, remove]}
 * @property bool                         $changeEmail
 * @property bool                         $piecePriceTag
 * @property bool                         $platePriceTag
 * @property bool                         $b2b
 * @property bool                         $b2c
 * @property string|null                  $qr
 *
 * @property-read string                  $title          {virtual}
 * @property-read string                  $dimensions     {virtual}
 * @property-read int                     $count          {virtual}
 * @property-read bool                    $hasPlates      {virtual}
 * @property-read bool                    $hasNotes       {virtual}
 * @property-read string                  $producerName   {virtual}
 */
class Stand extends BaseEntity
{
    use DeletableTrait;

    public const TYPE_PEACES = 1;
    public const TYPE_PLATES = 2;
    public const TYPE_SANITARY = 3;

    public const PLATE_ORDER_UNIVERSAL = 1;
    public const PLATE_ORDER_RIGHT_FIRST = 2;

    public function getterTitle(): string
    {
        return $this->name . ' (' . $this->code . ')';
    }

    public function getterDimensions(): string
    {
        return $this->width . 'x' . $this->depth . 'x' . $this->height;
    }

    public function getterCount(): int
    {
        return $this->standNotes->toCollection()->findBy(['removeDate' => null])->countStored();
    }

    public function getterHasPlates(): bool
    {
        return $this->type === self::TYPE_PLATES;
    }

    public function getterHasNotes(): bool
    {
        return $this->standNotes->count() > 0;
    }

    public function getterProducerName(): string
    {
        return $this->producer->name ?? 'Mix';
    }

    public function loadPlates(): array
    {
        return $this->plates->toCollection()->findBy(['deleted' => false])->orderBy('order')->fetchPairs('order');
    }

    public function getPlate(): ?StandPlate
    {
        return $this->plates->toCollection()->findBy(['deleted' => false])->orderBy('order')->fetch();
    }
}
