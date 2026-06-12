<?php
declare(strict_types=1);

namespace App\Modules\CliModule\Service;

use App\Core\Utils\DateTime;
use App\Modules\DeliveryModule\Orm\CustomerComplaints\CustomerComplaint;
use App\Modules\DeliveryModule\Orm\DeliveryItems\DeliveryItem;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\DeliveryModule\Orm\SalesData\SalesDataAccess;
use App\Modules\StockModule\Orm\WarehousemanHours\WarehousemanHour;
use App\Modules\StockModule\Orm\Warehousemen\Warehouseman;
use App\Modules\SystemModule\Orm\Phones\Phone;
use App\Modules\SystemModule\Orm\Users\User;
use App\Service\OrmModel;
use Nette\Security\Passwords;
use Nette\Utils\Strings;
use Nextras\Dbal\IConnection;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Collection\Expression\LikeExpression;

class Migrator
{
    private OrmModel $orm;
    private IConnection $db;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
        $this->db = $orm->users->getMapper()->getConnection();
    }

    public function migrateUsers(): void
    {
        $sql = 'SELECT * FROM [jas_uzivatel]';

        foreach ($this->db->query($sql)->fetchAll() as $oldUser) {
            $sql = 'SELECT * FROM [jas_prihlaseni] WHERE `id_uzivatel` = %i ORDER BY `cas_pripojeni` DESC';
            $logins = $this->db->query($sql, $oldUser->id_uzivatel)->fetchAll();

            $user = new User();
            $user->name = $oldUser->jmeno;
            $user->email = $oldUser->email ?: 'unknown@koupelny-jas.cz';
            $user->username = Strings::webalize($oldUser->jmeno);
            $user->internalLogin = $oldUser->login;
            $user->password = (new Passwords(PASSWORD_BCRYPT))->hash($oldUser->heslo ?: 'undefined');
            $user->store = $this->orm->stores->getById(explode('x', $oldUser->login)[0]);
            $user->loginCounter = count($logins);
            $user->lastLogin = $user->loginCounter ? $logins[0]->cas_pripojeni : null;
            $user->createdAt = $oldUser->registrace;
            $user->createdBy = $this->orm->users->getById(1);
            $user->deleted = !$oldUser->aktivni;

            if ($user->deleted) {
                $user->deletedBy = $this->orm->users->getById(1);
                $user->deletedAt = $oldUser->ukonceni;
            }

            if ($oldUser->pevna_linka) {
                $phone = new Phone();
                $phone->number = $oldUser->pevna_linka;
                $phone->description = 'Pevná linka';
                $user->phones->add($phone);
            }

            if ($oldUser->mobil) {
                $phone = new Phone();
                $phone->number = $oldUser->mobil;
                $phone->description = 'Mobil';
                $user->phones->add($phone);
            }

            if ($oldUser->interni_pevna) {
                $phone = new Phone();
                $phone->number = $oldUser->interni_pevna;
                $phone->description = 'Interní pevná';
                $user->phones->add($phone);
            }

            $this->orm->users->persist($user);
        }

        $this->orm->flush();
    }

    public function migrateWarehousemen(): void
    {
        $sql = 'SELECT * FROM [jas_skladnici]';

        foreach ($this->db->query($sql)->fetchAll() as $oldWarehouseman) {
            $warehouseman = new Warehouseman();
            $warehouseman->name = $oldWarehouseman->name;
            $warehouseman->webId = $oldWarehouseman->web_id;
            $warehouseman->createdAt = $oldWarehouseman->created_at;
            $warehouseman->createdBy = $this->orm->users->getById(1);

            if ($oldWarehouseman->cancelled_at) {
                $warehouseman->deletedBy = true;
                $warehouseman->deletedAt = $oldWarehouseman->cancelled_at;
                $warehouseman->deletedBy = $this->orm->users->getById(1);
            }

            $this->orm->warehousemen->persist($warehouseman);
        }

        $sql = 'SELECT * FROM [jas_sklad_prac_doba]';

        foreach ($this->db->query($sql)->fetchAll() as $oldWorkingHour) {
            $workingHour = new WarehousemanHour();
            $workingHour->date = $oldWorkingHour->date;
            $workingHour->length = $oldWorkingHour->length;
            $workingHour->createdAt = new DateTimeImmutable();
            $workingHour->createdBy = $this->orm->users->getById(1);

            $this->orm->warehousemanHours->persist($workingHour);
        }

        $this->orm->flush();
    }

    public function migrateDeliveryItems(): void
    {
        $storeNameMap = [
            'sumperk' => 1,
            'olomouc' => 2,
            'otrokovice' => 3,
            'jizdarna' => 4,
            'prostejov' => 5,
            'teplice' => 6,
            'valmez' => 7,
            'hradec' => 8,
            'michalkovice' => 9,
            'hlucin' => 10
        ];

        $stores = $this->orm->stores->findAll()->fetchPairs('id');
        $sql = 'SELECT * FROM [jas_expedice_sklad]';

        foreach ($this->db->query($sql)->fetchAll() as $oldItem) {
            $item = new DeliveryItem();
            $item->store = $stores[$storeNameMap[$oldItem->pobocka]];
            $item->number = $oldItem->dodak;
            $item->issueYear = $oldItem->rok_vystaveni_dl;
            $item->dispatchStart = $oldItem->prvni_expedice;
            $item->dispatchEnd = $oldItem->finalni_expedice;
            $item->remark = $oldItem->poznamka;

            $this->orm->deliveryItems->persist($item);
        }

        $this->orm->deliveryItems->flush();
    }

    public function migrateComplaints(): void
    {
        $storeNameMap = [
            'sumperk' => 1,
            'olomouc' => 2,
            'otrokovice' => 3,
            'jizdarna' => 4,
            'prostejov' => 5,
            'teplice' => 6,
            'valmez' => 7,
            'hradec' => 8,
            'michalkovice' => 9,
            'hlucin' => 10
        ];

        $userMap = [
            'Kvapil Antonín, Ing.' => 'Kvapil Antonín',
            'Veronika Chudášová' => 'Chudášová Veronika'
        ];

        $stores = $this->orm->stores->findAll()->fetchPairs('id');
        $items = $this->orm->stockItems->findAll()->fetchPairs('regNumber');
        $users = $this->orm->users->findAll()->fetchPairs('name');

        foreach ($userMap as $oldUsername => $currentUsername) {
            $users[$oldUsername] = $users[$currentUsername];
        }

        $sql = "
            SELECT * FROM [jas_reklamace_zakaznik_seznam] AS r
            LEFT JOIN [jas_reklamace_zakaznik_detail] AS d USING (id_reklamace)
            LEFT JOIN [jas_reklamace_zakaznik_vyjadreni] AS v USING (id_reklamace)
            WHERE r.datum >= '2020-01-01 00:00:00'
        ";

        foreach ($this->db->query($sql)->fetchAll() as $oldComplaint) {
            // migruji se data za posledni rok a u kterych zname cislo produktu
            if (!isset($items[$oldComplaint->produkt_index])) {
                continue;
            }

            $complaint = new CustomerComplaint();
            $complaint->store = $stores[$storeNameMap[$oldComplaint->id_pobocka]];
            $complaint->item = $items[$oldComplaint->produkt_index];
            $complaint->name = "$oldComplaint->zakaznik_jmeno $oldComplaint->zakaznik_prijmeni";
            $complaint->company = $oldComplaint->zakaznik_firma ?: null;
            $complaint->createdAt = $oldComplaint->datum;
            $complaint->createdBy = $users[$oldComplaint->autor];
            $complaint->description = [
                'street' => $oldComplaint->zakaznik_ulice,
                'zipCode' => $oldComplaint->zakaznik_psc,
                'city' => $oldComplaint->zakaznik_obec,
                'phone' => $oldComplaint->zakaznik_telefon,
                'email' => $oldComplaint->zakaznik_email,
                'deliveryItem' => $oldComplaint->produkt_dl,
                'bill' => $oldComplaint->produkt_faktura,
                'amount' => $oldComplaint->produkt_mnozstvi,
                'price' => $oldComplaint->produkt_cena,
                'buyDate' => $oldComplaint->produkt_zakoupeno->format(DateTime::CZ_DATE),
                'description' => $oldComplaint->produkt_zavada,
                'request' => $oldComplaint->zakaznik_pozadavek
            ];

            if ($oldComplaint->id_vyjadreni) {
                $complaint->state = CustomerComplaint::STATE_RESPONDED;
                $complaint->response = $oldComplaint->vyjadreni;
                $complaint->updatedAt = $oldComplaint->datum_vyjadreni;
                $complaint->updatedBy = $users[$oldComplaint->zpracoval ?: $oldComplaint->autor];
            } elseif ($oldComplaint->varovani_odeslano) {
                $complaint->state = CustomerComplaint::STATE_NOTIFIED;
            }

            $this->orm->customerComplaints->persist($complaint);
        }

        $this->orm->customerComplaints->flush();
    }

    public function migrateSalesDataAccess(): void
    {
        $usernameSql = 'SELECT `jmeno` FROM [jas_uzivatel] WHERE `id_uzivatel` = %i';
        $sql = 'SELECT * FROM [jas_analyza_prodeje_uzivatel]';

        foreach ($this->db->query($sql)->fetchAll() as $oldItem) {
            $username = $this->db->query($usernameSql, $oldItem->id_uzivatel)->fetch()->jmeno;
            $access = new SalesDataAccess();
            $access->store = $oldItem->id_stredisko;
            $access->user = $this->orm->users->getBy(['name' => $username]);
            $this->orm->salesDataAccess->persist($access);
        }

        $this->orm->salesDataAccess->flush();
    }

    public function migrateStavbaHradec(): void
    {
        foreach ($this->orm->stores->findAll()->fetchPairs(null, 'id') as $storeId) {
             $stavbaDepot = $this->orm->companyDepots->getBy([
                 'store->id' => $storeId,
                 'company->id' => 1,
                 'voj' => '88'
             ]);

             $notesCollection = $this->orm->deliveryNotes->findBy([
                 'store->id' => $storeId,
                 'movementType' => DeliveryNote::TRANSFER_TYPES,
                 'description~' => LikeExpression::contains('stavba'),
                 'depot->id!=' => $stavbaDepot->id
             ]);

             foreach ($notesCollection as $note) {
                 $note->depot = $stavbaDepot;
                 $this->orm->deliveryNotes->persist($note);
             }

            $this->orm->deliveryNotes->flush();
        }
    }

    public function deliveryItemsFix(int $year): int
    {
        $fixCount = 0;
        $items = $this->orm->deliveryItems->findBy([
            'issueYear' => $year,
            'createdAt>' => "$year-12-31"
        ]);

        foreach ($items as $deliveryItem) {
            $note = $this->orm->deliveryNotes->getBy([
                'number' => $deliveryItem->number,
                'store->id' => $deliveryItem->store->id,
                'movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_TRANSFER_OUT],
                'date>=' => "$deliveryItem->issueYear-01-01",
                'date<=' => "$deliveryItem->issueYear-12-31"
            ]);

            if (!$note) {
                continue;
            }

            $state = null;

            switch ($deliveryItem->state) {
                case DeliveryItem::STATE_PREPARED:
                    $state = DeliveryNote::STATE_PREPARED;
                    break;
                case DeliveryItem::STATE_OPEN_DISPATCH:
                    $state = DeliveryNote::STATE_DISPATCHING;
                    break;
                case DeliveryItem::STATE_LOADED:
                    $state = DeliveryNote::STATE_DONE;
            }

            if ($state && $state !== $note->state) {
                $note->state = $state;
                $this->orm->deliveryNotes->persistAndFlush($note);
                $fixCount++;
            }
        }

        return $fixCount;
    }
}