<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Transports;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\DeletableTrait;
use App\Core\Orm\Traits\LockableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Modules\TransportModule\Orm\Cars\StoreCar;
use App\Modules\TransportModule\Orm\Drivers\StoreDriver;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                                $id             {primary}
 * @property Store                              $store          {m:1 Store, oneSided=true}
 * @property StoreCar                           $car            {m:1 StoreCar::$storeTransports}
 * @property StoreDriver|null                   $driver         {m:1 StoreDriver::$storeTransports}
 * @property DateTimeImmutable                  $date
 * @property float                              $timeFrom
 * @property float                              $timeTill
 * @property int                                $type           {enum self::TYPE_*} {default self::TYPE_TRANSPORT}
 * @property int|null                           $reason         {enum self::REASON_*}
 * @property string|null                        $reasonRemark
 * @property OneHasMany|StoreTransportTarget[]  $targets        {1:m StoreTransportTarget::$transport}
 *
 * @property-read int                           $totalWeight    {virtual}
 * @property-read bool                          $isValid        {virtual}
 * @property array                              $errors         {virtual}
 * @property array                              $deliveryNotes  {virtual}
 * @property-read array                         $customers      {virtual}
 */
class StoreTransport extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;
    use DeletableTrait;
    use LockableTrait;

    public const TYPE_TRANSPORT = 1;
    public const TYPE_UNAVAILABILITY = 2;

    public const REASON_DRIVER_VACATION = 1;
    public const REASON_CAR_SERVICE = 2;
    public const REASON_OTHER = 3;
    public const REASONS_LABELS = [
        self::REASON_DRIVER_VACATION => 'Dovolená',
        self::REASON_CAR_SERVICE => 'Servis',
        self::REASON_OTHER => 'Jiné'
    ];

    public const ERROR_UNSET_PARTS = 1;
    public const ERROR_ADDRESS = 2;
    public const ERROR_TARIFF = 3;
    public const ERROR_PAYMENT = 4;
    public const ERROR_ITEM = 5;
    public const ERROR_WEIGHT = 6;
    public const ERROR_NO_MOP_NOTE = 7;
    public const ERRORS_LABELS = [
        self::ERROR_UNSET_PARTS => 'Nejsou naplánovány všechny jízdy',
        self::ERROR_ADDRESS => 'Není zadaná adresa doručení',
        self::ERROR_TARIFF => 'Není zadán tarif',
        self::ERROR_PAYMENT => 'Není zadána úhrada',
        self::ERROR_ITEM => 'Není zadán dodací list',
        self::ERROR_WEIGHT => 'Nesouhlasí zadaná váha s DL',
        self::ERROR_NO_MOP_NOTE => 'Dodací list nenalezen'
    ];

    public function isRedundant(): bool
    {
        return !$this->isLocked && $this->type === self::TYPE_TRANSPORT && !$this->targets->count();
    }

    public function isEditable(): bool
    {
        return !$this->deleted
            && (!$this->isLocked || $this->isSelfLocked)
            && $this->date >= ((new \DateTime())->modify('-1 day')->setTime(0, 0));
    }

    public function removeTargets(): void
    {
        foreach ($this->targets as $target) {
            foreach ($target->items as $item) {
                $item->getRepository()->removeAndFlush($item);
            }
            $target->getRepository()->removeAndFlush($target);
        }
    }

    public function loadForeignParts(): array
    {
        $foreignParts = [];
        foreach ($this->targets as $target) {
            foreach ($target->items as $item) {
                $itemForeignParts = $item->loadForeignParts();

                if ($itemForeignParts) {
                    $foreignParts[$target->id][$item->id] = $itemForeignParts;
                }
            }
        }
        return $foreignParts;
    }

    public function getterTotalWeight(): int
    {
        return array_sum($this->targets->toCollection()->fetchPairs(null, 'itemsWeight'));
    }

    public function getterDeliveryNotes(): array
    {
        $notes = [];
        foreach ($this->targets as $target) {
            foreach ($target->items as $item) {
                $notes[] = $item->deliveryNoteLabel;
            }
        }
        return $notes;
    }

    public function getSqlTargets(): DbColumn
    {
        return new DbColumn('targets->name');
    }

    public function getSqlDeliveryNotes(): DbColumn
    {
        return new DbColumn('targets->items->deliveryNote');
    }

    public function getterErrors(): array
    {
        if ($this->type === self::TYPE_UNAVAILABILITY) {
            return [];
        }

        $errors = [];

        foreach ($this->targets as $target) {
            if (!isset($errors[self::ERROR_ADDRESS]) && !$target->address) {
                $errors[self::ERROR_ADDRESS] = self::ERRORS_LABELS[self::ERROR_ADDRESS];
            }
            if (!isset($errors[self::ERROR_TARIFF]) && !$target->tariff) {
                $errors[self::ERROR_TARIFF] = self::ERRORS_LABELS[self::ERROR_TARIFF];
            }
            if (!isset($errors[self::ERROR_PAYMENT]) && !$target->payment) {
                $errors[self::ERROR_PAYMENT] = self::ERRORS_LABELS[self::ERROR_PAYMENT];
            }
            if (!isset($errors[self::ERROR_ITEM]) && !$target->items->count()) {
                $errors[self::ERROR_ITEM] = self::ERRORS_LABELS[self::ERROR_ITEM];
            }

            foreach ($target->items as $item) {
                if (!isset($errors[self::ERROR_UNSET_PARTS]) && $item->loadUnsetPartCount() > 0) {
                    $errors[self::ERROR_UNSET_PARTS] = self::ERRORS_LABELS[self::ERROR_UNSET_PARTS];
                }
                if (!isset($errors[self::ERROR_WEIGHT]) && $item->weightError) {
                    $errors[self::ERROR_WEIGHT] = self::ERRORS_LABELS[self::ERROR_WEIGHT];
                }
                if (!isset($errors[self::ERROR_NO_MOP_NOTE]) && !$item->hasMopNote) {
                    $errors[self::ERROR_NO_MOP_NOTE] = self::ERRORS_LABELS[self::ERROR_NO_MOP_NOTE];
                }
            }
        }

        return $errors;
    }

    public function getterIsValid(): bool
    {
        return count($this->errors) === 0;
    }

    public function getterCustomers(): array
    {
        $customers = [];
        foreach ($this->targets as $target)  {
            foreach ($target->items as $item) {
                $customer = $item->customer;
                if ($customer) {
                    $customers[] = $customer;
                }
            }
        }
        return array_unique($customers);
    }

    /** Call beforeUpdate for set updated columns */
    public function onBeforeUpdate(): void
    {
    }
}
