<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Transports;

use App\Core\Orm\BaseEntity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                                $id             {primary}
 * @property StoreTransport                     $transport      {m:1 StoreTransport::$targets}
 * @property string                             $name
 * @property string                             $phone
 * @property string|null                        $address
 * @property int|null                           $tariff         {enum self::TARIFF_*}
 * @property int|null                           $payment        {enum self::PAYMENT_*}
 * @property string|null                        $remark
 * @property OneHasMany|StoreTransportItem[]    $items          {1:m StoreTransportItem::$target, cascade=[persist, remove]}
 *
 * @property-read int                           $itemsWeight    {virtual}
 */
class StoreTransportTarget extends BaseEntity
{
    public const TARIFF_UNDER_35 = 1;
    public const TARIFF_UNDER_65 = 2;
    public const TARIFF_OVER_65 = 3;
    public const TARIFF_UNDER_15 = 4;

    public const TARIFFS_LABELS = [
        self::TARIFF_UNDER_15 => 'A - do 15 km',
        self::TARIFF_UNDER_35 => 'B - do 35 km',
        self::TARIFF_UNDER_65 => 'C - do 65 km',
        self::TARIFF_OVER_65 => 'D - nad 65 km'
    ];
    public const TARIFFS_SHORT_LABELS = [
        self::TARIFF_UNDER_15 => 'A',
        self::TARIFF_UNDER_35 => 'B',
        self::TARIFF_UNDER_65 => 'C',
        self::TARIFF_OVER_65 => 'D'
    ];

    public const PAYMENT_PAID = 1;
    public const PAYMENT_PLUS_CHARGE = 2;
    public const PAYMENT_INVOICE = 3;
    public const PAYMENT_NOT_CHARGED = 4;
    public const PAYMENTS_LABELS = [
        self::PAYMENT_PAID => 'uhrazeno',
        self::PAYMENT_PLUS_CHARGE => 'doplatek',
        self::PAYMENT_INVOICE => 'fakturou',
        self::PAYMENT_NOT_CHARGED => 'nehradí se'
    ];

    public function getterItemsWeight(): int
    {
        return array_sum($this->items->toCollection()->fetchPairs(null, 'weight'));
    }

    public function isLockedByDriver(int $driverId): bool
    {
        $item = $this->items->toCollection()->fetch();
        return $item && $item->isLockedByDriver($driverId);
    }

    public function hasDriverEditableItems(): bool
    {
        foreach ($this->items as $item) {
            if (!$item->delivered || $item->canUndoDelivered()) {
                return true;
            }
        }
        return false;
    }
}
