<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Transports;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Utils\DateTime;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNoteRepository;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Modules\SystemModule\Orm\Users\User;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                                  $id                 {primary}
 * @property StoreTransportTarget                 $target             {m:1 StoreTransportTarget::$items}
 * @property Store                                $store              {m:1 Store, oneSided=true}
 * @property int                                  $deliveryNote
 * @property int                                  $year
 * @property int                                  $weight
 * @property bool                                 $delivered          {default false}
 * @property User|null                            $setDeliveredBy     {m:1 User, oneSided=true}
 * @property DateTimeImmutable|null               $setDeliveredAt
 * @property OneHasMany|StoreTransportItemPart[]  $parts              {1:m StoreTransportItemPart::$item, cascade=[persist, remove]}
 *
 * @property-read string                          $deliveryNoteLabel  {virtual}
 * @property-read string                          $car                {virtual}
 * @property-read float                           $transportTimeFrom  {virtual}
 * @property-read float                           $transportTimeTill  {virtual}
 * @property-read DateTimeImmutable               $transportDate      {virtual}
 * @property-read int                             $partCount          {virtual}
 * @property-read bool                            $hasParts           {virtual}
 * @property-write StoreTransportItemPart[]       $foreignParts       {virtual}
 * @property-write DeliveryNote|null              $mopNote            {virtual}
 * @property-write bool                           $mopNoteLoaded      {virtual}
 * @property-read string|null                     $weightError        {virtual}
 * @property-read bool                            $hasMopNote         {virtual}
 * @property-read string|null                     $customer           {virtual}
 * @property-read string                          $targetName         {virtual}
 * @property-read array                           $partNumbers        {virtual}
 */
class StoreTransportItem extends BaseEntity
{
    public function canUndoDelivered(): bool
    {
        if (!$this->setDeliveredAt || !$this->delivered) {
            return true;
        }
        $lockTime = 15;
        return $this->setDeliveredAt->modify("+$lockTime minutes") > new DateTimeImmutable();
    }

    public function isLockedByDriver(int $driverId): bool
    {
        $lockTime = 15;
        $lastTrigger = $this->getLastTriggeredItem($driverId);
        return $lastTrigger
            && $lastTrigger->target->transport->id !== $this->target->transport->id
            && $lastTrigger->setDeliveredAt->modify("+$lockTime minutes") > new DateTimeImmutable();
    }

    public function getterDeliveryNoteLabel(): string
    {
        return $this->store->id . '/' . $this->deliveryNote . '/' . $this->year;
    }

    public function getterTransportTimeFrom(): float
    {
        return $this->target->transport->timeFrom;
    }

    public function getterTransportTimeTill(): float
    {
        return $this->target->transport->timeTill;
    }

    public function getterTransportDate(): DateTimeImmutable
    {
        return $this->target->transport->date;
    }

    public function getterCar(): string
    {
        return $this->target->transport->car->licensePlate;
    }

    public function getSqlTransportTimeFrom(): DbColumn
    {
        return new DbColumn('target->transport->timeFrom');
    }

    public function getSqlDeliveryNoteLabel(): DbColumn
    {
        return new DbColumn('deliveryNote');
    }

    public function getSqlCar(): DbColumn
    {
        return new DbColumn('target->transport->car->id');
    }

    public function getSqlTransportDate(): DbColumn
    {
        return new DbColumn('target->transport->date');
    }

    public function getterPartCount(): int
    {
        return intval(ceil($this->weight / $this->target->transport->car->weightCapacity));
    }

    public function getterHasParts(): bool
    {
        return $this->partCount > 1;
    }

    public function getterWeightError(): ?string
    {
        $deliveryNote = $this->loadMopNode();

        if (!$deliveryNote) {
            return null;
        }

        $noteWeight = $deliveryNote->weight;

        if (abs($noteWeight - $this->weight) > 10) {
            return "Váha dle DL nesouhlasí - $noteWeight kg";
        }

        return null;
    }

    public function getterHasMopNote(): bool
    {
        return !is_null($this->loadMopNode());
    }

    public function getterCustomer(): ?string
    {
        $mopNote = $this->loadMopNode();
        if (!$mopNote) {
            return null;
        }
        return $mopNote->depot ? $mopNote->depot->companyName : $mopNote->description;
    }

    public function getterTargetName(): string
    {
        return $this->target->name;
    }

    public function getSqlTargetName(): DbColumn
    {
        return new DbColumn('target->name');
    }

    public function getterPartNumbers(): array
    {
        $partNumbers = [];
        $selfTransportPartCount = $this->parts->toCollection()->findBy(['type' => StoreTransportItemPart::TYPE_SELF_PART])->count();
        $foreignParts = $this->loadForeignParts();
        $totalTransportPartCount = $selfTransportPartCount;
        $startNumber = 1;

        foreach ($foreignParts as $foreignPart) {
            if ($foreignPart->type !== StoreTransportItemPart::TYPE_SELF_PART) {
                continue;
            }
            $totalTransportPartCount++;
            $foreignDate = $foreignPart->item->target->transport->date->setTime((int) $foreignPart->item->target->transport->timeFrom, 0);
            $selfDate = $this->target->transport->date->setTime((int) $this->target->transport->timeFrom, 0);
            if ($foreignDate < $selfDate) {
                $startNumber++;
            }
        }

        for ($i = 1; $i <= $selfTransportPartCount; $i++) {
            $partNumbers[$startNumber++] = $totalTransportPartCount;
        }

        return $partNumbers;
    }

    public function loadForeignParts(): array
    {
        if (isset($this->foreignParts)) {
            return $this->foreignParts;
        }
        $foreignParts = [];
        /** @var StoreTransportItemRepository $repo */
        $repo = $this->getRepository();
        $foreignTransportItems = $repo->findBy([
            'target->id!=' => $this->target->id,
            'target->transport->deleted' => false,
            'store->id' => $this->store->id,
            'deliveryNote' => $this->deliveryNote,
            'year' => $this->year
        ])->orderBy('target->transport->date');

        foreach ($foreignTransportItems as $foreignTransportItem) {
            foreach ($foreignTransportItem->parts as $part) {
                $foreignParts[] = $part;
            }
        }

        return $this->foreignParts = $foreignParts;
    }

    public function loadUnsetPartCount(): int
    {
        if (!$this->hasParts) {
            return 0;
        }
        return $this->partCount - count($this->loadForeignParts()) - $this->parts->count();
    }

    public function loadMopNode(): ?DeliveryNote
    {
        if (empty($this->mopNoteLoaded)) {
            $deliveryNoteRepo = $this->getRepository()->getModel()->getRepository(DeliveryNoteRepository::class);
            $this->mopNote = $deliveryNoteRepo->getByTransportItem($this->store->id, $this->deliveryNote, $this->year);
            $this->mopNoteLoaded = true;
        }

        return $this->mopNote;
    }

    private function getLastTriggeredItem(int $driverId): ?self
    {
        return $this->getRepository()->findBy([
            'setDeliveredBy->id' => $driverId,
            'target->transport->date' => date(DateTime::DB_DATE)
        ])->orderBy('setDeliveredAt', ICollection::DESC)->fetch();
    }
}
