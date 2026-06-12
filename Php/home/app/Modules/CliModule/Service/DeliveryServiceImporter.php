<?php
declare(strict_types=1);

namespace App\Modules\CliModule\Service;

use App\Core\Orm\BaseMapper;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNoteRepository;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Service\OrmModel;

/**
 * Sluzba pro import placenych sluzeb
 */
class DeliveryServiceImporter
{
    private OrmModel $orm;
    private MovementImporter $movementImporter;

    public function __construct(OrmModel $orm, MovementImporter $movementImporter)
    {
        $this->orm = $orm;
        $this->movementImporter = $movementImporter;
    }

    /** Doimportovani sluzeb k dodacim listum */
    public function importNoteServices(int $storeId): string
    {
        $file = DATA_DIR . "/sluzby/$storeId-pohyby-sluzeb.csv";

        if (!file_exists($file)) {
            return "Import neproběhl, nenalezen soubor $storeId-pohyby-sluzeb.csv";
        }

        $fileContent = file_get_contents($file);
        $deliveryServices = $this->orm->deliveryServices->findAll()->fetchPairs('regNumber', 'id');
        $noteServices = [];
        $unknownLines = [];

        $separator = "\r\n";
        $line = strtok($fileContent, $separator);

        while ($line !== false) {
            $data = str_getcsv($line, ';');
            $movementNumber = (int) trim($data[0]);
            $noteNumber = (int) trim($data[1]);
            $serviceRegNumber = (int) trim($data[2]);
            $amount = (float) $data[4];
            $sellPrice = (float) $data[5];
            $buyPrice = (float) $data[6];
            $discount = (float) $data[7];
            $date = \DateTime::createFromFormat('d.m.Y', $data[8])->format('Y-m-d');

            $note = $this->orm->deliveryNotes->getBy([
                'store->id' => $storeId,
                'number' => $noteNumber,
                'date' => $date,
                'movementNumber' => $movementNumber
            ]);

            if (!$note) {
                $unknownLines[] = $line;
                $line = strtok($separator);
                continue;
            }

            if (!isset($deliveryServices[$serviceRegNumber])) {
                $deliveryServices[$serviceRegNumber] = $this->movementImporter->createUnknownService($serviceRegNumber);
            }

            $noteServices[] = [
                'note' => $note->id,
                'service' => $deliveryServices[$serviceRegNumber],
                'amount' => $amount,
                'sell_price' => $sellPrice,
                'buy_price' => $buyPrice,
                'discount' => $discount
            ];

            $line = strtok($separator);
        }

        if (!$noteServices) {
            return 'Žádná nová data nebyla nalezena';
        }

        $this->orm->deliveryNoteServices->insertItems($noteServices);

        if ($unknownLines) {
            file_put_contents($file, implode($separator, $unknownLines));
        } else {
            unlink($file);
        }

        return 'Naimportováno služeb na dokladech: ' . count($noteServices);
    }

    /** Doimportovani chybejicich hlavice dokladu k polozkam sluzeb */
    public function importMissingNotes(int $storeId): string
    {
        $serviceItemsFile = DATA_DIR . "/sluzby/$storeId-pohyby-sluzeb.csv";

        if (!file_exists($serviceItemsFile)) {
            return "Import neproběhl, nenalezen soubor $storeId-pohyby-sluzeb.csv";
        }

        $fileContent = file_get_contents($serviceItemsFile);
        $noteServices = [];
        $separator = "\r\n";
        $line = strtok($fileContent, $separator);

        while ($line !== false) {
            $data = str_getcsv($line, ';');
            $movementNumber = (int) trim($data[0]);
            $noteNumber = (int) trim($data[1]);
            $date = \DateTime::createFromFormat('d.m.Y', $data[8])->format('Y-m-d');

            $noteServices[$noteNumber][$movementNumber][$date] = true;
            $line = strtok($separator);
        }

        if (!$noteServices) {
            return 'Žádná nová data nebyla nalezena';
        }

        $notesFile = DATA_DIR . "/sluzby/$storeId-hlavicky_dokladu_historicky.csv";

        if (!file_exists($notesFile)) {
            return "Import neproběhl, nenalezen soubor $storeId-hlavicky_dokladu_historicky.csv";
        }

        $fileContent = file_get_contents($notesFile);
        $notes = [];
        $depots = $this->orm->companyDepots->loadStoreDepots($storeId);
        $line = strtok($fileContent, $separator);

        while ($line !== false) {
            $data = str_getcsv($line, ';');
            $noteNumber = (int) $data[1];
            $movementNumber = (int) ltrim(trim($data[2]), '0');
            $date = \DateTime::createFromFormat('d.m.Y', $data[4]);
            $movementType = DeliveryNoteRepository::getMovementType($movementNumber);

            if (!$movementType) {
                // Zajimaji nas jen urcite typu pohybu
                $line = strtok($separator);
                continue;
            }

            if (!isset($noteServices[$noteNumber][$movementNumber][$date->format('Y-m-d')])) {
                $line = strtok($separator);
                continue;
            }

            $season = $data[0] ? intval($data[0]) : null;
            $description = trim($data[3]);
            $ico = $data[4] ? intval(ltrim(trim($data[5]), '0')) : null;
            $voj = trim($data[6]);
            $bill = trim($data[7]);
            $deliveryNote = trim($data[8]);
            $stateChar = $data[9];
            $cancelNote = trim($data[10]) ? intval($data[10]) : null;
            $state = $movementType === DeliveryNote::TYPE_SALE || $movementType === DeliveryNote::TYPE_TRANSFER_OUT
                ? DeliveryNote::STATE_DONE : null;

            if (in_array($movementType, DeliveryNote::TRANSFER_TYPES)) {
                // Prevodky maji jako ico dane ID pobocky
                $internalIco = Store::INTERNAL_ICO;
                $depotKey = $internalIco . BaseMapper::DATA_STRING_SEPARATOR . $ico;
            } elseif ($ico) {
                $depotKey = $ico . BaseMapper::DATA_STRING_SEPARATOR . $voj;

                if (!isset($depots[$depotKey])) {
                    // Udaje o firme jsou smazane
                    $depots[$depotKey] = $this->movementImporter->createUnknownDepot($storeId, $ico, $voj);
                }
            }

            $notes[] = [
                'store' => $storeId,
                'number' => $noteNumber,
                'date' => $date,
                'description' => $description,
                'season' => $season,
                'state_char' => $stateChar,
                'state' => $state,
                'movement_number' => $movementNumber,
                'movement_type' => $movementType,
                'depot' => $ico ? $depots[$depotKey] : null,
                'bill' => $bill ?: null,
                'depot_note' => $deliveryNote ?: null,
                'cancel_note' => $cancelNote
            ];

            $line = strtok($separator);
        }

        if (!$notes) {
            return 'Žádná nová data nebyla nalezena';
        }

        $this->orm->deliveryNotes->insertItems($notes);
        $result = $this->importNoteServices($storeId);

        return 'Naimportováno hlavicek dokladu: ' . count($notes) . " + $result";
    }
}
