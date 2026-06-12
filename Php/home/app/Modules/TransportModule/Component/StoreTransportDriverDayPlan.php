<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Component;

use App\Modules\TransportModule\Orm\Transports\StoreTransportItem;
use App\Modules\TransportModule\Orm\Transports\StoreTransportTarget;
use App\Service\OrmModel;
use Nette\Application\UI\Control;
use Nextras\Dbal\Utils\DateTimeImmutable;

class StoreTransportDriverDayPlan extends Control
{
    private OrmModel $orm;
    private int $driverId;

    public function __construct(OrmModel $orm, int $driverId)
    {
        $this->orm = $orm;
        $this->driverId = $driverId;
    }

    public function handleCheckTransportItem(): void
    {
        $itemId = (int) $this->getPresenter()->getParameter('itemId');
        $isChecked = (bool) $this->getPresenter()->getParameter('isChecked');
        $item = $this->orm->storeTransportItems->getById($itemId);

        if ($item && $this->canEditItem($item)) {
            $user = $this->orm->users->getById($this->getPresenter()->getUser()->getId());
            $item->delivered = $isChecked;
            $item->setDeliveredAt = new DateTimeImmutable();
            $item->setDeliveredBy = $user;
            $this->orm->storeTransportItems->persistAndFlush($item);
        }

        $this->redrawControl('storeDriverDayPlan');
    }

    public function handleCheckTransportTarget(): void
    {
        $targetId = (int) $this->getPresenter()->getParameter('targetId');
        $isChecked = (bool) $this->getPresenter()->getParameter('isChecked');
        $target = $this->orm->storeTransportTargets->getById($targetId);

        if ($target) {
            foreach ($target->items as $item) {
                if ($this->canEditItem($item)) {
                    $user = $this->orm->users->getById($this->getPresenter()->getUser()->getId());
                    $item->delivered = $isChecked;
                    $item->setDeliveredAt = new DateTimeImmutable();
                    $item->setDeliveredBy = $user;
                    $this->orm->storeTransportItems->persist($item);
                }
            }
            $this->orm->storeTransportItems->flush();
        }

        $this->redrawControl('storeDriverDayPlan');
    }

    public function render(): void
    {
        $transports = [];

        if ($this->driverId) {
            $transports = $this->orm->storeTransports->findDriverDayTransports($this->driverId)->fetchAll();
        }

        $this->template->transports = $transports;
        $this->template->setFile(__DIR__ . '/templates/storeTransportDriverDayPlan.latte');
        $this->template->render();
    }

    /** Kontrola editace - editovat muze jen
     *   - admin
     *   - uzivatel s danou roli
     *   - ridic svuj rozvoz, zrusit dovezenou polozku muze jen po urcitou dobu
     */
    public function canEditItem(StoreTransportItem $item): bool
    {
        $user = $this->getPresenter()->getUser();

        if ($user->isAllowed(':Transport:StoreTransport:selectDriver')) {
            return true;
        }

        return $user->getId() === ($item->target->transport->driver->user->id ?? 0)
            && !$item->isLockedByDriver($user->getId())
            && (!$item->delivered || $item->canUndoDelivered());
    }

    public function canEditTarget(StoreTransportTarget $target): bool
    {
        $user = $this->getPresenter()->getUser();

        if ($user->isAllowed(':Transport:StoreTransport:selectDriver')) {
            return true;
        }

        return $user->getId() === ($target->transport->driver->user->id ?? 0)
            && !$target->isLockedByDriver($user->getId())
            && $target->hasDriverEditableItems();
    }
}
