<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DeliveryNotes;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbString;
use App\Modules\StockModule\Orm\StockItems\StockVariant;
use Nextras\Dbal\Utils\DateTimeImmutable;

/**
 * @property int                          $id               {primary}
 * @property DeliveryNote                 $note             {m:1 DeliveryNote::$items}
 * @property StockVariant                 $item             {m:1 StockVariant::$noteItems}
 * @property float                        $amount
 * @property float                        $sellPrice
 * @property float                        $buyPrice
 * @property float                        $discount
 * @property int                          $tax
 * @property int|null                     $outletType       {enum StockVariant::OUTLET_*}
 *
 * @property-read int                     $noteNumber       {virtual}
 * @property-read string                  $name             {virtual}
 * @property-read string                  $unit             {virtual}
 * @property-read float                   $sellSum          {virtual}
 * @property-read float                   $buySum           {virtual}
 * @property-read int                     $movementNumber   {virtual}
 * @property-read int                     $movementType     {virtual}
 * @property-read string                  $store            {virtual}
 * @property-read DateTimeImmutable       $date             {virtual}
 */
class NoteItem extends BaseEntity
{
    public function getterNoteNumber(): int
    {
        return $this->note->number;
    }

    public function getterName(): string
    {
        $name = $this->item->regNumber . ' - ' . $this->item->name;
        if ($this->item->catalog) {
            $name .= ' (' . $this->item->catalogTitle . ')';
        }
        return $name;
    }

    public function getterUnit(): string
    {
        return $this->item->unit;
    }

    public function getterSellSum(): float
    {
        return ($this->amount * $this->sellPrice) - $this->discount;
    }

    public function getterBuySum(): float
    {
        return $this->amount * $this->buyPrice;
    }

    public function getterMovementNumber(): int
    {
        return $this->note->movementNumber;
    }

    public function getterStore(): string
    {
        return $this->note->store->name;
    }

    public function getterMovementType(): int
    {
        return $this->note->movementType;
    }

    public function getterDate(): DateTimeImmutable
    {
        return $this->note->date;
    }

    public function getSqlStore(): DbColumn
    {
        return new DbColumn('note->store->id');
    }

    public function getSqlDate(): DbColumn
    {
        return new DbColumn('note->date');
    }

    public function getSqlName(): DbFunction
    {
        return new DbFunction(
            'CONCAT_WS',
            new DbString("' '"),
            new DbColumn('item->item->regNumber'),
            new DbColumn('item->item->name'),
            new DbColumn('item->catalog->name')
        );
    }

    public function getSqlMovementType(): DbColumn
    {
        return new DbColumn('note->movementType');
    }
}
