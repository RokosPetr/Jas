<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockItems;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\NoteItem;
use App\Modules\StockModule\Orm\CatalogNumbers\CatalogNumber;
use App\Modules\StockModule\Orm\StockSectors\StockSector;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                       $id             {primary}
 * @property StockItem                 $item           {m:1 StockItem::$variants}
 * @property string                    $supplement
 * @property string|null               $remark
 * @property CatalogNumber|null        $catalog        {m:1 CatalogNumber::$variants}
 * @property Store                     $store          {m:1 Store, oneSided=true}
 * @property StockSector|null          $sector         {m:1 StockSector::$variants}
 * @property float                     $quantity
 * @property float|null                $paletteQuantity
 * @property int|null                  $weight
 * @property bool                      $sample
 * @property bool                      $deleted
 * @property int|null                  $outletType     {enum self::OUTLET_*}
 * @property OneHasMany|NoteItem[]     $noteItems      {1:m NoteItem::$item}
 *
 * @property-read string               $unit           {virtual}
 * @property-read string               $name           {virtual}
 * @property-read string               $regNumber      {virtual}
 * @property-read string|null          $sectorName     {virtual}
 * @property-read string               $producer       {virtual}
 * @property-read string|null          $catalogTitle   {virtual}
 * @property-read string               $series         {virtual}
 */
class StockVariant extends BaseEntity
{
    public const OUTLET_TILES = 1;
    public const OUTLET_SANITARY = 2;
    public const OUTLETS_TYPES = [
        self::OUTLET_TILES => 'Obklady a dlažby',
        self::OUTLET_SANITARY => 'Sanita'
    ];

    public function getterUnit(): string
    {
        return $this->item->unit->name ?? '-';
    }

    public function getterName(): string
    {
        return $this->item->name;
    }

    public function getSqlName(): DbColumn
    {
        return new DbColumn('item->name');
    }

    public function getterProducer(): string
    {
        return $this->item->producer->name ?? '???';
    }

    public function getSqlProducer(): DbColumn
    {
        return new DbColumn('item->producer->name');
    }

    public function getterRegNumber(): string
    {
        return $this->item->regNumber;
    }

    public function getSqlRegNumber(): DbColumn
    {
        return new DbColumn('item->regNumber');
    }

    public function getterSectorName(): ?string
    {
        return $this->sector->name ?? null;
    }

    public function getSqlSectorName(): DbColumn
    {
        return new DbColumn('sector->name');
    }

    public function getterCatalogTitle(): ?string
    {
        return $this->catalog->name ?? null;
    }

    public function getSqlCatalogTitle(): DbColumn
    {
        return new DbColumn('catalog->name');
    }

    public function getSqlProducerId(): DbColumn
    {
        return new DbColumn('item->producer->id');
    }

    public function getSqlGlobalProducerId(): DbColumn
    {
        return new DbColumn('item->globalProducer');
    }

    public function getterSeries(): string
    {
        return $this->item->seriesName;
    }

    public function getSqlSeries(): DbColumn
    {
        return new DbColumn('item->series->id');
    }

    public function loadSideQuantity(): array
    {
        $collection = $this->noteItems->toCollection()
            ->findBy([
                'note->state' => [
                    DeliveryNote::STATE_RESERVATION,
                    DeliveryNote::STATE_PREPARATION,
                    DeliveryNote::STATE_LOADING,
                    DeliveryNote::STATE_PREPARED,
                    DeliveryNote::STATE_DISPATCHING
                ]
            ]);

        if ($collection->count() === 0) {
            return [];
        }

        $sideQuantity = [
            DeliveryNote::STATE_RESERVATION => 0,
            DeliveryNote::STATE_PREPARATION => 0,
            DeliveryNote::STATE_LOADING => 0,
            DeliveryNote::STATE_PREPARED => 0,
            DeliveryNote::STATE_DISPATCHING => 0
        ];

        foreach ($collection as $noteItem) {
            $sideQuantity[$noteItem->note->state] += $noteItem->amount;
        }

        return $sideQuantity;
    }
}
