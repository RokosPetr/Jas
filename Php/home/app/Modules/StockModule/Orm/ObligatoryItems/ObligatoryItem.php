<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\ObligatoryItems;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbCondition;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbMath;
use App\Core\Orm\Expresion\DbString;
use App\Modules\StockModule\Orm\StockItems\StockItem;
use App\Modules\StockModule\Orm\StockItems\StockVariant;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                          $id             {primary}
 * @property StockItem                    $item           {1:1 StockItem::$obligatoryItem, isMain=true}
 * @property float                        $quantity
 * @property float                        $minOrder
 * @property OneHasMany|Order[]           $orders         {1:m Order::$obligatoryItem, cascade=[persist, remove]}
 *
 * @property-read string                  $regNumber      {virtual}
 * @property-read string                  $name           {virtual}
 * @property-read string                  $producer       {virtual}
 * @property-read string                  $series         {virtual}
 * @property-read string                  $stockGroup     {virtual}
 * @property-read string                  $unit           {virtual}
 * @property-read StockVariant            $stockVariant   {virtual}
 * @property-read float                   $storeQuantity  {virtual}
 * @property-read string|null             $catalog        {virtual}
 * @property-read bool                    $belowLimit     {virtual}
 * @property-read Order|null              $storeOrder     {virtual}
 * @property-read float|null              $orderSum       {virtual}
 * @property-read bool                    $hasOrder       {virtual}
 */
class ObligatoryItem extends BaseEntity
{
    public function getterRegNumber(): string
    {
        return $this->item->regNumber;
    }

    public function getSqlRegNumber(): DbColumn
    {
        return new DbColumn('item->regNumber');
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

    public function getterStockGroup(): string
    {
        return $this->item->group->name;
    }

    public function getSqlProducerId(): DbColumn
    {
        return new DbColumn('item->producer->id');
    }

    public function getSqlGlobalProducerId(): DbColumn
    {
        return new DbColumn('item->globalProducer');
    }

    public function getterUnit(): string
    {
        return $this->item->unit->name ?? '-';
    }

    public function getterStockVariant(): StockVariant
    {
        return $this->item->variants->toCollection()
            ->findBy(['store->id' => ObligatoryItemRepository::getStore()])
            ->orderBy('quantity', ICollection::DESC)
            ->fetch();
    }

    public function getterStoreQuantity(): float
    {
        return $this->stockVariant->quantity;
    }

    public function getSqlStoreQuantity(): DbFunction
    {
        return new DbFunction(
            'stock_get_item_quantity',
            new DbColumn('item->id'),
            new DbString((string) ObligatoryItemRepository::getStore())
        );
    }

    public function getterCatalog(): ?string
    {
        return $this->item->catalog;
    }

    public function getterBelowLimit(): bool
    {
        return $this->quantity > $this->storeQuantity;
    }

    public function getSqlBellowLimit(): DbMath
    {
        return new DbMath(new DbColumn('quantity'), '>', $this->getSqlStoreQuantity(),);
    }

    public function getterStoreOrder(): ?Order
    {
        return $this->orders->toCollection()->getBy(['store->id' => ObligatoryItemRepository::getStore()]);
    }

    public function getterHasOrder(): bool
    {
        return !is_null($this->storeOrder);
    }

    public function getterOrderSum(): ?float
    {
        return $this->storeOrder->orderSum ?? null;
    }

    public function getSqlOrderSum(): DbFunction
    {
        return new DbFunction(
            'stock_get_item_order_sum',
            new DbColumn('id'),
            new DbString((string) ObligatoryItemRepository::getStore())
        );
    }

    public function getSqlHasOrder(): DbFunction
    {
        return new DbFunction(
            'IF',
            new DbCondition(
                new DbMath($this->getSqlOrderSum(), '>', new DbString('0'))
            ),
            new DbString('1'),
            new DbString('0')
        );
    }

    public function getSqlCatalog(): DbColumn
    {
        return new DbColumn('item->catalogs->name');
    }

    public function getterSeries(): string
    {
        $series = $this->item->series->toCollection()->fetchPairs(null, 'name');
        return $series ? implode(', ', $series) : '-';
    }

    public function getSqlSeries(): DbColumn
    {
        return new DbColumn('item->series->id');
    }
}
