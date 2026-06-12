<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockItems;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Modules\StockModule\Orm\CatalogNumbers\CatalogNumber;
use App\Modules\StockModule\Orm\MainStorageOrders\MainStorageOrderItem;
use App\Modules\StockModule\Orm\ObligatoryItems\ObligatoryItem;
use App\Modules\StockModule\Orm\ObligatoryItems\ObligatoryItemRepository;
use App\Modules\StockModule\Orm\Producers\Producer;
use App\Modules\StockModule\Orm\StockGroups\StockGroup;
use App\Modules\StockModule\Orm\StockSeries\StockSeries;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Modules\SystemModule\Orm\Users\User;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Relationships\ManyHasMany;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                                $id                   {primary}
 * @property string                             $regNumber
 * @property string                             $name
 * @property Producer|null                      $producer             {m:1 Producer, oneSided=true}
 * @property StockGroup|null                    $group                {m:1 StockGroup, oneSided=true}
 * @property Unit|null                          $unit                 {m:1 Unit, oneSided=true}
 * @property float|null                         $package
 * @property float|null                         $palette
 * @property float|null                         $price
 * @property int|null                           $minOrder
 * @property int|null                           $globalProducer       {enum self::GLOBAL_PRODUCER_*}
 * @property int                                $status               {enum self::STATUS_*} {default self::STATUS_COMMISSION}
 * @property DateTimeImmutable|null             $statusChangedAt
 * @property User|null                          $statusChangedBy      {m:1 User, oneSided=true}
 * @property DateTimeImmutable|null             $inactiveFrom
 *
 * @property OneHasMany|StockVariant[]          $variants             {1:m StockVariant::$item}
 * @property OneHasMany|CatalogNumber[]         $catalogs             {1:m CatalogNumber::$item}
 * @property ManyHasMany|StockSeries[]          $series               {m:m StockSeries::$items}
 * @property ObligatoryItem|null                $obligatoryItem       {1:1 ObligatoryItem::$item}
 * @property OneHasMany|MainStorageOrderItem[]  $mainStorageOrders    {1:m MainStorageOrderItem::$item}
 *
 * @property-read string|null                   $storeSectors         {virtual}
 * @property-read string|null                   $catalog              {virtual}
 * @property-read string                        $storageCatalog       {virtual}
 * @property-read float                         $mainStorageQuantity  {virtual}
 * @property-read float                         $mainStorageOrder     {virtual}
 * @property-read string                        $seriesName           {virtual}
 * @property-read string                        $title                {virtual}
 * @property-read bool                          $isActive             {virtual}
 */
class StockItem extends BaseEntity
{
    public const STATUS_COMMISSION = 1;
    public const STATUS_PALETTE = 2;
    public const STATUS_DISCARDED = 3;

    public const STATUSES = [
        StockItem::STATUS_COMMISSION => 'KOMIS',
        StockItem::STATUS_PALETTE => 'Paleta',
        StockItem::STATUS_DISCARDED => 'Vyřazeno'
    ];

    public const OSTRAVA_MAIN_STORAGE_PRODUCERS = [
        1, 2, 3, 5, 6, 7, 11, 12, 14, 21, 26, 27, 28, 29, 53, 54, 55, 56, 57, 58
    ];

    public const GLOBAL_PRODUCER_JAS = 1;
    public const GLOBAL_PRODUCER_TOOLS = 2;

    public function getterMainStorageQuantity(): float
    {
        $variantCollection = $this->variants->toCollection();
        // Ostrava-Michalkovice je hlavni sklad pro nektere vyrobce, ostatni jsou ve sklade v Hlucine
        if (in_array($this->producer->number ?? 0, self::OSTRAVA_MAIN_STORAGE_PRODUCERS)) {
            $variantCollection = $variantCollection->findBy(['store->id' => Store::OSTRAVA_MAIN_STORAGE]);
        } else {
            $variantCollection = $variantCollection->findBy(['store->id' => Store::HLUCIN_MAIN_STORAGE]);
        }

        $mainStorageQuantities = $variantCollection->fetchPairs(null, 'quantity');
        return $mainStorageQuantities ? max($mainStorageQuantities) : 0;
    }

    public function getterTitle(): string
    {
        return "$this->regNumber - $this->name";
    }

    public function getterIsActive(): bool
    {
        return is_null($this->inactiveFrom);
    }

    public function getterStoreSectors(): ?string
    {
        return implode(', ', array_unique(
            $this->variants->toCollection()->findBy([
                'store->id' => ObligatoryItemRepository::getStore(),
                'sector->id!=' => null
            ])->fetchPairs(null, 'sector->name'))
        );
    }

    public function getterCatalog(): ?string
    {
        return $this->catalogs->count()
            ? implode(', ', $this->catalogs->toCollection()->fetchPairs(null, 'name'))
            : null;
    }

    public function getSqlCatalog(): DbColumn
    {
        return new DbColumn('catalogs->name');
    }

    public function getterStorageCatalog(): string
    {
        return $this->variants->toCollection()->getBy([
            'store->id' => Store::OSTRAVA_MAIN_STORAGE,
            'catalog->id!=' => null
        ])->catalogTitle ?? '';
    }

    public function getSqlStorageCatalog(): DbColumn
    {
        return $this->getSqlCatalog();
    }

    public function getterSeriesName(): string
    {
        $series = $this->series->toCollection()->fetchPairs(null, 'name');
        return $series ? implode(', ', $series) : '-';
    }

    public function getSqlGlobalProducer(): DbColumn
    {
        return new DbColumn('globalProducer');
    }

    public function getterMainStorageOrder(): float
    {
        return array_sum($this->mainStorageOrders->toCollection()->findBy(['stocked' => false])->fetchPairs(null, 'quantity'));
    }

    public function loadStoreMaxQuantities(): array
    {
        $storeQuantities = [];

        foreach ($this->variants as $variant) {
            if (!isset($storeQuantities[$variant->store->id])) {
                $storeQuantities[$variant->store->id] = 0;
            }

            if ($storeQuantities[$variant->store->id] < $variant->quantity) {
                $storeQuantities[$variant->store->id] = $variant->quantity;
            }
        }

        return $storeQuantities;
    }

    public function getSqlProducer(): DbColumn
    {
        return new DbColumn('producer->id');
    }

    public function getExportProducer(): string
    {
        return $this->producer->name ?? '';
    }

    public function getExportGroup(): string
    {
        return (string) ($this->group->number ?? '');
    }

    public function getExportStatus(): string
    {
        return self::STATUSES[$this->status];
    }

    public function getExportUnit(): string
    {
        return $this->unit->name ?? '';
    }

    public function getExportMainStorageQuantity(): string
    {
        return (string) $this->mainStorageQuantity;
    }

    public function getExportPalette(): string
    {
        return str_replace('.', ',', $this->palette ?? '');
    }

    public function getExportPackage(): string
    {
        return str_replace('.', ',', $this->package ?? '');
    }
}
