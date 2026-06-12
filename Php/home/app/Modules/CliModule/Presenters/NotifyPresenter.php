<?php
declare(strict_types=1);

namespace App\Modules\CliModule\Presenters;

use App\Core\Utils\DateTime;
use App\Modules\BathroomModule\Orm\Bathrooms\BathPicture;
use App\Modules\CliModule\Service\WarehouseImporter;
use App\Modules\DeliveryModule\Orm\CustomerComplaints\CustomerComplaint;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\Presenters\BasePresenter;
use App\Modules\StockModule\Orm\MainStorageOrders\MainStorageOrder;
use App\Modules\StockModule\Orm\ObligatoryItems\ObligatoryItemRepository;
use App\Modules\StockModule\Orm\StockItems\StockItem;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Modules\TransportModule\Orm\Transports\StoreTransport;
use App\Modules\TransportModule\Service\StoreTransportService;
use Nextras\Orm\Collection\ICollection;

/** Presenter pro import dat */
final class NotifyPresenter extends BasePresenter
{
    public array $titles = [
        'default' => 'Upozornění'
    ];

    /** @inject */
    public StoreTransportService $storeTransportService;

    /** @inject */
    public WarehouseImporter $warehouseImporter;

    public function renderDefault(): void
    {
        $this->template->stores = $this->orm->stores->findAll();
        $this->template->notifies = [
            'customerComplaints' => 'Reklamace zákazníků',
            'stockItemPrice' => 'Aktualizace cen produktů',
            'stockItemInactiveState' => 'Aktualizace zrušených položek',
            'mainStorageOrders' => 'Aktualizace objednávek velkoskladu',
            'deliveryNoteDuplicity' => 'Kontrola duplicit hlaviček dokladu',
            'emptyStockItemData' => 'Kontrola prazdnych karet zbozi',
            'emptyDeliveryNotes' => 'Kontrola prázdných dokladů',
            'invalidStoreTransports' => 'Kontrola maloobchodní dopravy',
            'bathPictureThumbnails' => 'Generovat zmenšeniny 3D fotek koupelen'
        ];
    }

    /** Zasilani upozorneni, pokud doba pro vyrizeni reklamace je jen 10 dni */
    public function actionCustomerComplaints(): void
    {
        $counter = 0;

        foreach ($this->orm->customerComplaints->findBy(['state' => CustomerComplaint::STATE_NEW]) as $complaint) {
            if ($complaint->daysLeft <= 10) {
                $this->mailer->sendCustomerComplaintNotification($complaint);
                $complaint->state = CustomerComplaint::STATE_NOTIFIED;
                $complaint->getRepository()->persistAndFlush($complaint);
                $counter++;
            }
        }

        $this->template->notificationCount = $counter;
    }

    /** Odstraneni objednavky povinne polozky pri jejim naskladneni o objednane mnozstvi */
    public function actionObligatoryItemOrders(int $id): void
    {
        $counter = 0;
        ObligatoryItemRepository::setStore($id);

        foreach ($this->orm->obligatoryItemOrders->findBy(['store->id' => $id]) as $order) {
            $intake = $this->orm->deliveryNoteItems->getBy([
                'note->store->id' => $id,
                'item->item->id' => $order->obligatoryItem->item->id,
                'note->date>' => $order->createdAt,
                'note->movementType' => DeliveryNote::TYPE_TRANSFER_IN,
                'amount>=' => $order->orderSum
            ]);

            if ($intake) {
                $this->orm->obligatoryItemOrders->removeAndFlush($order);
                $counter++;
            }
        }

        $this->template->store = $this->orm->stores->getById($id)->name;
        $this->template->notificationCount = $counter;
    }

    /** Oznaceni objednavek velkoskladu za naskladnene pro prijeti zbozi */
    public function actionMainStorageOrders(): void
    {
        $counter = 0;

        foreach ($this->orm->mainStorageOrderItems->findBy(['stocked' => false]) as $orderItem) {
            $intakes = $this->orm->deliveryNoteItems->findBy([
                'note->store->id' => Store::MAIN_STORAGES,
                'item->item->id' => $orderItem->item->id,
                'note->date>' => $orderItem->order->createdAt,
                'note->movementType' => DeliveryNote::TYPE_TAKINGS
            ])->fetchPairs(null, 'amount');

            if (array_sum($intakes) >= $orderItem->quantity) {
                $orderItem->stocked = true;
                $this->orm->mainStorageOrderItems->persistAndFlush($orderItem);
                $counter++;
                $orderItem->order->state = MainStorageOrder::STATE_COMPLETELY_STOCKED;

                foreach ($orderItem->order->items as $item) {
                    if (!$item->stocked) {
                        $orderItem->order->state = MainStorageOrder::STATE_PARTLY_STOCKED;
                        break;
                    }
                }

                $orderItem->order->getRepository()->persistAndFlush($orderItem->order);
            }
        }

        $this->template->notificationCount = $counter;
    }

    /** Odesle upozorneni v pripade objeveni duplicitniho dokladu v současném roku */
    public function actionDeliveryNoteDuplicity(): void
    {
        $storeDuplicities = $this->orm->deliveryNotes->loadDuplicities();

        if (!$storeDuplicities) {
            $this->flashMessage('Žádné duplicity nenalezeny');
            $this->redirect('default');
        }

        $counter = 0;
        $storeNamesDuplicities = [];

        foreach ($storeDuplicities as $storeId => $duplicities) {
            $counter += count($duplicities);
            $store = $this->orm->stores->getById($storeId);
            $storeNamesDuplicities[$store->name] = $duplicities;
        }

        $this->mailer->sendDeliveryNoteDuplicity($storeNamesDuplicities);
        $this->flashMessage("Nalezeny duplicity: $counter");
        $this->redirect('default');
    }

    /** Odesle upozorneni v pripade objeveni prazdne karty polozky sortimentu */
    public function actionEmptyStockItemData(): void
    {
        $stockItems = $this->orm->stockItems->findBy([
            'name' => '???',
            'status!=' => StockItem::STATUS_DISCARDED
        ]);

        $counter = $stockItems->count();

        if ($counter) {
            $this->mailer->sendEmptyStockItemData($stockItems->fetchPairs(null, 'regNumber'));
        }

        $this->flashMessage("Nalezené prázdné karty: $counter");
        $this->redirect('default');
    }

    /** Odesle upozorneni v pripade objeveni prazdne prazdneho dodaciho listu */
    public function actionEmptyDeliveryNotes(): void
    {
        $emptyNotes = $this->orm->deliveryNotes->findBy([
            'items->id' => null,
            'services->id' => null
        ]);
        $counter = $emptyNotes->count();

        if ($counter) {
            $this->mailer->sendEmptyDeliveryNotes($emptyNotes->orderBy('store->id')->fetchAll());
        }

        $this->flashMessage("Nalezené prázdné doklady: $counter");
        $this->redirect('default');
    }

    /** Odesle mail na pobocku v pripade nevalidni naplanovane prepravy pro dnesek a zitrek */
    public function actionInvalidStoreTransports(): void
    {
        $today = new \DateTimeImmutable();
        $tomorrow = $today->modify('+1 day');
        $counter = 0;

        foreach ([$today, $tomorrow] as $date) {
            $errorEmails = [];

            $transports = $this->orm->storeTransports->findBy([
                'deleted' => false,
                'date' => $date->format(DateTime::DB_DATE),
                'type' => StoreTransport::TYPE_TRANSPORT
            ])->orderBy(['car->id' => ICollection::ASC, 'timeFrom' => ICollection::ASC]);

            foreach ($transports as $transport) {
                if ($transport->isValid || !$transport->updatedBy->store) {
                    continue;
                }

                $errorEmails[$transport->updatedBy->store->id] ??= [];
                $errorEmails[$transport->updatedBy->store->id][] = $transport;
                $counter++;
            }

            foreach ($errorEmails as $storeId => $errorTransports) {
                $store = $this->orm->stores->getById($storeId);
                $this->mailer->sendInvalidStoreTransport($store, $errorTransports);
            }
        }

        $this->flashMessage("Nalezeny nevalidni prepravy: $counter");
        $this->redirect('default');
    }

    public function actionStockItemPrice(): void
    {
        $error = $this->warehouseImporter->updateItemPrice();

        if ($error) {
            $this->flashMessage('ERROR: ' . $error, self::MSG_ERROR);
        } else {
            $this->flashMessage('Ceny zboží aktualizovány');
        }

        $this->redirect('default');
    }

    public function actionStockItemGroup(): void
    {
        $error = $this->warehouseImporter->updateItemGroup();

        if ($error) {
            $this->flashMessage('ERROR: ' . $error, self::MSG_ERROR);
        } else {
            $this->flashMessage('Skupiny zboží aktualizovány');
        }

        $this->redirect('default');
    }

    public function actionStockItemInactiveState(): void
    {
        $error = $this->warehouseImporter->updateItemInactiveState();

        if ($error) {
            $this->flashMessage('ERROR: ' . $error, self::MSG_ERROR);
        } else {
            $this->flashMessage('Zrušené položky zboží aktualizovány');
        }

        $this->redirect('default');
    }

    public function actionBathPictureThumbnails(): void
    {
        set_time_limit(300);
        $counter = 0;
        $updates = [];

        foreach ($this->orm->bathrooms->findBy(['deleted' => false]) as $bathroom) {
            $file = $bathroom->virtualPicture;

            if (!$file) {
                continue;
            }

            $filename = 'thumbnail_' . BathPicture::PICTURE_3D_WIDTH . '_' . BathPicture::PICTURE_3D_QUALITY . '_' . $file->name;
            $link = ROOT_DIR . "/$file->path/$filename";

            if (!file_exists($link)) {
                $updates[] = $file->thumbnail(BathPicture::PICTURE_3D_WIDTH, BathPicture::PICTURE_3D_QUALITY);
                $counter = count($updates);

                if ($counter === 10) {
                    break;
                }
            }
        }

        $this->flashMessage("Vygenerováno $counter fotek");
        $this->redirect('default');
    }
}
