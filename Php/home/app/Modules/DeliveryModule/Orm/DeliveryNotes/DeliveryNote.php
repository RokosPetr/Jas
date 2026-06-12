<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DeliveryNotes;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbCondition;
use App\Core\Orm\Expresion\DbString;
use App\Core\Utils\DateTime;
use App\Modules\DeliveryModule\Orm\Companies\Depot;
use App\Modules\DeliveryModule\Orm\DeliveryItems\DeliveryItemRepository;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                            $id               {primary}
 * @property Store                          $store            {m:1 Store, oneSided=true}
 * @property int                            $number
 * @property DateTimeImmutable              $date
 * @property string                         $description
 * @property int|null                       $season
 * @property string                         $stateChar
 * @property int|null                       $state            {enum self::STATE_*}
 * @property int                            $movementNumber
 * @property int                            $movementType     {enum self::TYPE_*}
 * @property Depot|null                     $depot            {m:1 Depot::$deliveries}
 * @property string|null                    $bill
 * @property string|null                    $depotNote
 * @property int|null                       $cancelNote
 * @property DeliveryNote|null              $parent           {1:1 DeliveryNote::$child, isMain=true}
 * @property DeliveryNote|null              $child            {1:1 DeliveryNote::$parent}
 * @property bool                           $checked          {default false}
 * @property float                          $netSum
 * @property float                          $grossSum
 * @property float                          $taxSum
 * @property string|null                    $remark
 *
 * @property OneHasMany|NoteItem[]          $items            {1:m NoteItem::$note}
 * @property OneHasMany|NoteService[]       $services         {1:m NoteService::$note}
 *
 * @property-read string                    $partner          {virtual}
 * @property-read int                       $weight           {virtual}
 * @property-read float                     $sellSum          {virtual}
 * @property-read float                     $buySum           {virtual}
 * @property-read float                     $profit           {virtual}
 * @property-read bool                      $hasChild         {virtual}
 * @property-read bool                      $hasParent        {virtual}
 * @property-read string|null               $transferError    {virtual}
 * @property-read string|null               $warehouseRemark  {virtual}
 */
class DeliveryNote extends BaseEntity
{
    public const TYPE_SALE = 1;
    public const TYPE_TAKINGS = 2;
    public const TYPE_CANCEL = 3;
    public const TYPE_TRANSFER_IN = 4;
    public const TYPE_TRANSFER_OUT = 5;

    public const TRANSFER_TYPES = [self::TYPE_TRANSFER_IN, self::TYPE_TRANSFER_OUT];

    public const STATE_RESERVATION = 1; //rezervace
    public const STATE_PREPARATION = 2; //předchystáno
    public const STATE_LOADING = 3;     //navezeno
    public const STATE_DONE = 4;        //ukončeno
    public const STATE_PREPARED = 5;    //předchystáno
    public const STATE_DISPATCHING = 6; //částečně vyvezeno

    public const TRANSFER_SUM_PRECISION = 10;

    public function getterWeight(): int
    {
        $weight = 0;
        foreach ($this->items as $noteItem) {
            $weight += $noteItem->amount * ($noteItem->item->weight ?? 0);
        }
        return intval(round($weight / 1000));
    }

    public function getterPartner(): string
    {
        return $this->depot ? $this->depot->companyName : 'FO';
    }

    public function getExportDate(): string
    {
        return $this->date->format(DateTime::CZ_DATE);
    }

    public function getExportMovementNumber(): string
    {
        return str_pad((string) $this->movementNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getterSellSum(): float
    {
        return array_sum($this->items->toCollection()->fetchPairs(null, 'sellSum'))
            + array_sum($this->services->toCollection()->fetchPairs(null, 'sellSum'));
    }

    public function getterBuySum(): float
    {
        return array_sum($this->items->toCollection()->fetchPairs(null, 'buySum'));
    }

    public function getterProfit(): float
    {
        return $this->netSum - $this->taxSum - $this->buySum;
    }

    public function getterTransferError(): ?string
    {
        if ($this->movementType !== self::TYPE_TRANSFER_IN) {
            return null;
        }

        if (!$this->parent) {
            return 'Výdejní doklad nenalezen';
        }

        $buySumDiff = round(abs($this->buySum - $this->parent->buySum));
        if ($buySumDiff > self::TRANSFER_SUM_PRECISION) {
            return "Rozdíl v sumě nákupních cen je $buySumDiff Kč";
        }

        $sellSum = round(abs($this->sellSum - $this->parent->sellSum));
        if ($sellSum > self::TRANSFER_SUM_PRECISION) {
            return "Rozdíl v sumě prodejních cen je $sellSum Kč";
        }

        return null;
    }

    public function getterHasChild(): bool
    {
        return !is_null($this->child);
    }

    public function getterHasParent(): bool
    {
        return !is_null($this->parent);
    }

    public function getSqlHasChild(): DbCondition
    {
        return new DbCondition(new DbColumn('child->id'), new DbString('IS NOT NULL'));
    }

    public function getSqlHasParent(): DbCondition
    {
        return new DbCondition(new DbColumn('parent->id'), new DbString('IS NOT NULL'));
    }

    public function getSqlHasItems(): DbCondition
    {
        return new DbCondition(new DbColumn('items->id'), new DbString('IS NOT NULL'));
    }

    public function getSqlDate(): DbColumn
    {
        return new DbColumn('date');
    }

    public function getSqlStateFilter(): DbColumn
    {
        return new DbColumn('state');
    }

    public function getSqlStore(): DbColumn
    {
        return new DbColumn('store->id');
    }

    public function loadBadTransferItems(): array
    {
        if ($this->movementType !== self::TYPE_TRANSFER_IN || !$this->hasParent || $this->parent->movementType !== self::TYPE_TRANSFER_OUT) {
            return [];
        }

        $badItems = [];
        $itemsOut = $this->parent->items->toCollection();

        foreach ($this->items as $itemIn) {
            $item = $itemsOut->getBy([
                'item->item->regNumber' => $itemIn->item->item->regNumber,
                'amount' => $itemIn->amount,
                'sellPrice' => $itemIn->sellPrice,
                'buyPrice' => $itemIn->buyPrice
            ]);

            if (!$item && !isset($badItems[$itemIn->item->regNumber])) {
                $badItems[$itemIn->item->regNumber] = $itemIn->item->name;
            }
        }

        return $badItems;
    }

    public function getterWarehouseRemark(): ?string
    {
        return $this->getRepository()->getModel()->getRepository(DeliveryItemRepository::class)->getBy([
            'store->id' => $this->store->id,
            'number' => $this->number,
            'issueYear' => (int) $this->date->format('Y')
        ])->remark ?? null;
    }
}
