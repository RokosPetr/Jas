<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DeliveryNotes;

use App\Core\Orm\BaseEntity;
use App\Modules\DeliveryModule\Orm\Services\Service;

/**
 * @property int                          $id               {primary}
 * @property DeliveryNote                 $note             {m:1 DeliveryNote::$services}
 * @property Service                      $service          {m:1 Service::$noteServices}
 * @property float                        $amount
 * @property float                        $sellPrice
 * @property float                        $buyPrice
 * @property float                        $discount
 * @property int                          $tax
 *
 * @property-read string                  $name             {virtual}
 * @property-read float                   $sellSum          {virtual}
 */
class NoteService extends BaseEntity
{
    public function getterSellSum(): float
    {
        return ($this->amount * $this->sellPrice) - $this->discount;
    }

    public function getterName(): string
    {
        return $this->service->regNumber . ' - ' . $this->service->name;
    }
}
