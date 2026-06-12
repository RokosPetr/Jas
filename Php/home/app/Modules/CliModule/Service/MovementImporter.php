<?php
declare(strict_types=1);

namespace App\Modules\CliModule\Service;

use App\Core\Orm\BaseMapper;
use App\Core\Utils\DateTime;
use App\Modules\CliModule\Service\Entity\DeliveryNoteImport;
use App\Modules\CliModule\Service\Entity\NoteItemImport;
use App\Modules\DeliveryModule\Orm\DeliveryItems\DeliveryItem;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNoteRepository;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Modules\SystemModule\Orm\StoreSettings\StoreSetting;
use App\Service\OrmModel;
use Nette\InvalidArgumentException;
use Nette\Utils\Strings;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Collection\Expression\LikeExpression;
use Nextras\Orm\Collection\ICollection;

/**
 * Sluzba pro import pohybu pobocek
 */
class MovementImporter
{
    private OrmModel $orm;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
    }

    private function loadDeliveryNotes(string $fileContent): array
    {
        $notes = [];
        $separator = "\r\n";
        $line = strtok(iconv('WINDOWS-1250', 'UTF-8', $fileContent), $separator);

        while ($line !== false) {
            $data = str_getcsv($line, ';');
            $movementNumber = (int) ltrim(trim($data[2]), '0');
            $movementType = DeliveryNoteRepository::getMovementType($movementNumber);

            if (!$movementType) {
                // Zajimaji nas jen urcite typu pohybu
                $line = strtok($separator);
                continue;
            }

            $note = new DeliveryNoteImport();
            $note->season = $data[0] ? intval($data[0]) : null;
            $note->number = (int) $data[1];
            $note->movementNumber = $movementNumber;
            $note->movementType = $movementType;
            $note->description = trim($data[3]);
            $note->date = \DateTime::createFromFormat('j. n. Y', $data[4]);
            $note->ico = (int) ltrim(trim($data[5]), '0');
            $note->voj = trim($data[6]);
            $note->bill = trim($data[7]);
            $note->deliveryNote = trim($data[8]);
            $note->state = $data[9];
            $note->cancelNote = trim($data[10]) ? intval($data[10]) : null;
            $note->netSum = (float) ($data[11] ?? 0);
            $note->grossSum = (float) ($data[12] ?? 0);
            $note->taxSum = (float) ($data[13] ?? 0);

            $notes[] = $note;
            $line = strtok($separator);
        }

        return $notes;
    }

    private function loadDeliveryItems(string $fileContent): array
    {
        $noteItems = [];
        $separator = "\r\n";
        $line = strtok(iconv('WINDOWS-1250', 'UTF-8', $fileContent), $separator);

        while ($line !== false) {
            $data = str_getcsv($line, ';');

            if (count($data) < 9) {
                // doslo ke spojeni dvou sloupcu, kdy text obsahuje uvozovky
                $data = array_map(
                    static fn(string $val) => rtrim($val, '"'),
                    explode(';', implode(';', $data))
                );
            }

            if (!DeliveryNoteRepository::getMovementType((int) trim($data[0]))) {
                // Zajimaji nas jen urcite typu pohybu
                $line = strtok($separator);
                continue;
            }

            $noteItem = new NoteItemImport();
            $noteItem->movementNumber = (int) trim($data[0]);
            $noteItem->noteNumber = (int) trim($data[1]);
            $noteItem->itemRegNumber = trim($data[2]);
            $noteItem->supplement = $data[3];
            $noteItem->amount = (float) $data[4];
            $noteItem->sellPrice = (float) $data[5];
            $noteItem->buyPrice = (float) $data[6];
            $noteItem->discount = (float) $data[7];
            $noteItem->date = \DateTime::createFromFormat('j. n. Y', $data[8]);
            $noteItem->isService = strlen($noteItem->itemRegNumber) < 7;
            $noteItem->tax = (int) trim($data[9] ?? '');

            $noteItems[] = $noteItem;
            $line = strtok($separator);
        }

        return $noteItems;
    }

    public function importCurrentMovements(int $storeId, bool $pairAllTransfers = true): string
    {
        $storeName = $this->orm->stores->getById($storeId)->name ?? '?';
        $importName = "Pohyby zboží - $storeName";
        $import = $this->orm->imports->getImportByName($importName);

        $zipName = 'intranet.zip';
        $fileName = "hlavicky_dokladu_aktualniho_roku.csv";
        $zip = new \ZipArchive();

        if ($zip->open(DATA_DIR . "/$storeId/$zipName") !== true) {
            return "Import neproběhl. Nelze načíst zdrojový soubor '$zipName'";
        }

        $fileStats = $zip->statName($fileName);

        if (!$fileStats) {
            $zip->close();
            return "Import neproběhl. Nelze načíst zdrojový soubor '$fileName'";
        }

        if ($import && $import->date->getTimestamp() >= $fileStats['mtime']) {
            $zip->close();
            return "Import neproběhl, data jsou aktuální (" . $import->date->format(DateTime::CZ_DATETIME) . ')';
        }

        $notes = $this->loadDeliveryNotes($zip->getFromName($fileName));

        $fileName = "pohyby_aktualniho_mesice.csv";
        $fileStats = $zip->statName($fileName);

        if (!$fileStats) {
            $zip->close();
            return "Import neproběhl. Nelze načíst zdrojový soubor '$fileName'";
        }

        $noteItems = $this->loadDeliveryItems($zip->getFromName($fileName));

        $fileName = "aktualni_ucetni_obdobi.csv";
        $fileStats = $zip->statName($fileName);

        if (!$fileStats) {
            $zip->close();
            return "Import neproběhl. Nelze načíst zdrojový soubor '$fileName'";
        }

        $currentTaxSeason = $zip->getFromName($fileName);
        $currentTaxSeasonSetting = $this->orm->storeSettings->getBy(['name' => StoreSetting::CURRENT_SEASON, 'store->id' => $storeId])
            ?? $this->orm->storeSettings->createSetting(StoreSetting::CURRENT_SEASON, $currentTaxSeason, $storeId);
        $isNextSeason = $currentTaxSeason !== $currentTaxSeasonSetting->value;
        $isNextYear = false;

        $taxSeasonData = explode(';', $currentTaxSeason);
        $taxMonth = trim($taxSeasonData[0]) ?? null;
        $taxYear = trim($taxSeasonData[1]) ?? null;

        if (!$taxMonth || !$taxYear) {
            return "Import neproběhl. Nelze načíst data o aktualním účetním období";
        }

        $season = intval($taxYear . str_pad($taxMonth, 2, '0', STR_PAD_LEFT));
        $this->orm->deliveryNotes->beginTransaction();

        if ($isNextSeason) {
            $lastSeasonData = explode(';', $currentTaxSeasonSetting->value);
            $lastTaxMonth = trim($lastSeasonData[0]) ?? null;
            $lastTaxYear = trim($lastSeasonData[1]) ?? null;

            if (!$lastTaxMonth || !$lastTaxYear) {
                return "Import neproběhl. Nelze načíst data o minulém účetním období";
            }

            $lastSeason = intval($lastTaxYear . str_pad($lastTaxMonth, 2, '0', STR_PAD_LEFT));
            $this->orm->deliveryNotes->deleteBySeason($storeId, $lastSeason);

            if ($taxYear !== $lastTaxYear) {
                $fileName = 'hlavicky_dokladu_prosinec_loni.csv';
                $fileStats = $zip->statName($fileName);

                if (!$fileStats) {
                    $zip->close();
                    return "Import neproběhl. Nelze načíst zdrojový soubor '$fileName'";
                }

                $lastNotes = $this->loadDeliveryNotes($zip->getFromName($fileName));
                $isNextYear = true;
            }
        }

        $this->orm->deliveryNotes->deleteByCurrentSeason($storeId, $season);

        if ($isNextYear) {
            $this->updateDeliveryNotes($storeId, $lastNotes, (int) $lastTaxYear);
        }

        $this->updateDeliveryNotes($storeId, $notes, (int) $taxYear);
        $this->importNoteItems($storeId, $noteItems, $season);

        if ($isNextSeason) {
            $fileName = "pohyby_minuleho_mesice.csv";
            $fileStats = $zip->statName($fileName);

            if (!$fileStats) {
                $zip->close();
                return "Import neproběhl. Nelze načíst zdrojový soubor '$fileName'";
            }

            $noteItems = $this->loadDeliveryItems($zip->getFromName($fileName));
            $this->importNoteItems($storeId, $noteItems, $lastSeason);
            $currentTaxSeasonSetting->value = $currentTaxSeason;
            $this->orm->storeSettings->persistAndFlush($currentTaxSeasonSetting);
        }

        $this->orm->deliveryNotes->commitTransaction();
        $zip->close();
        $this->findParentNotes($storeId);

        if ($pairAllTransfers) {
            $this->findTransferParents();
        }

        if (!$import) {
            $this->orm->imports->insertEntity(null, [
                'name' => $importName,
                'date' => (new DateTime())->setTimestamp($fileStats['mtime'])
            ]);
        } else {
            $import->date = (new DateTime())->setTimestamp($fileStats['mtime']);
            $import->getRepository()->persistAndFlush($import);
        }

        return "Import pohybů pobočky '$storeName' proběhl úspěšně";
    }

    /**
     * @param int $storeId
     * @param DeliveryNoteImport[] $imports
     */
    private function updateDeliveryNotes(int $storeId, array $imports, int $year): void
    {
        $depots = $this->orm->companyDepots->loadStoreDepots($storeId);
        $deliveryItems = $this->orm->deliveryItems->findBy(['store->id' => $storeId, 'issueYear' => $year])
            ->fetchPairs('number', 'state');
        $counter = 0;
        $updates = [];
        $updateCols = [
            'state_char', 'state', 'depot', 'bill', 'depot_note', 'season', 'description', 'cancel_note',
            'net_sum', 'gross_sum', 'tax_sum'
        ];

        foreach ($imports as $importData) {
            if ( $importData->description === '*** ZRUŠENÝ DOKLAD ***'
                //|| $importData->description === 'Korekce PNC'
                //|| $importData->description === 'Cenové rozdíly v NákC'
            ) {
                // Tyhle doklady nas nezajimaji
                continue;
            }

            $depotKey = null;

            //public const TRANSFER_TYPES = [self::TYPE_TRANSFER_IN, self::TYPE_TRANSFER_OUT];
            if (in_array($importData->movementType, DeliveryNote::TRANSFER_TYPES)) {
                // Prevodky maji jako ico dane ID pobocky
                $internalIco = Store::INTERNAL_ICO; //27792803
                $depotKey = $internalIco . BaseMapper::DATA_STRING_SEPARATOR . $importData->ico;
            } elseif ($importData->ico || $importData->voj) {
                $depotKey = $importData->ico . BaseMapper::DATA_STRING_SEPARATOR . $importData->voj;

                if (!isset($depots[$depotKey])) {
                    // Udaje o firme jsou smazane
                    $depots[$depotKey] = $this->createUnknownDepot($storeId, $importData->ico, $importData->voj);
                }
            }

            $counter++;
            $updates[] = [
                'store' => $storeId,
                'number' => $importData->number,
                'date' => $importData->date,
                'description' => $importData->description,
                'season' => $importData->season,
                'state_char' => $importData->state,
                'state' => $this->getDeliveryNoteState($importData, $deliveryItems[$importData->number] ?? null),
                'movement_number' => $importData->movementNumber,
                'movement_type' => $importData->movementType,
                'depot' => $depotKey ? $depots[$depotKey] : null,
                'bill' => $importData->bill ?: null,
                'depot_note' => $importData->deliveryNote ?: null,
                'cancel_note' => $importData->cancelNote,
                'net_sum' => $importData->netSum, //12
                'gross_sum' => $importData->grossSum, //13
                'tax_sum' => $importData->taxSum //14
            ];

            if ($counter === 500) {
                $this->orm->deliveryNotes->updateItems($updates, $updateCols);
                $counter = 0;
                $updates = [];
            }
        }

        if ($updates) {
            $this->orm->deliveryNotes->updateItems($updates, $updateCols);
        }
    }

    /** Fukce pro update dokladu dle pozadavku */
    public function updateDeliveryNoteData(int $storeId, string $fileContent): int
    {
        $counter = 0;

        /** @var DeliveryNoteImport $importData */
        foreach ($this->loadDeliveryNotes($fileContent) as $importData) {
            if (
                //$importData->description === 'Korekce PNC'
                //|| $importData->description === 'Cenové rozdíly v NákC'
                $importData->description === '*** ZRUŠENÝ DOKLAD ***'
                || $importData->ico === 0
                || $importData->movementType !== DeliveryNote::TYPE_SALE
            ) {
                // Tyhle doklady nas nezajimaji
                continue;
            }

            $deliveryNote = $this->orm->deliveryNotes->getBy([
                'store->id' => $storeId,
                'number' => $importData->number,
                'movementNumber' => $importData->movementNumber,
                'date' => $importData->date->format('Y-m-d')
            ]);

            if (!$deliveryNote) {
                throw new \InvalidArgumentException(
                    'Doklad nenalezen - ' . $importData->number . ' - ' . $importData->date->format('d.m.Y')
                );
            }

            if ($deliveryNote->depot && $deliveryNote->depot->voj === $importData->voj) {
                continue;
            }

            $depot = $this->orm->companyDepots->getBy([
                'store->id' => $storeId,
                'voj' => $importData->voj,
                'company->ico' => $importData->ico
            ]);

            if (!$depot) {
                throw new \InvalidArgumentException(
                    'Pobočka nenalezena - ' . $importData->ico . ' (' . $importData->voj .  ')'
                );
            }

            $deliveryNote->depot = $depot;
            $this->orm->deliveryNotes->persist($deliveryNote);
            $counter++;
        }

        $this->orm->deliveryNotes->flush();
        return $counter;
    }

    public function importDispatchedDeliveryItems(int $storeId, int $year, string $fileContent): string
    {
        $duplicities = [];
        $separator = "\r\n";
        $line = strtok($fileContent, $separator);
        $admin = $this->orm->users->getMainAdmin();
        $store = $this->orm->stores->getById($storeId);
        $savedNumbers = $this->orm->deliveryItems->findBy(['store->id' => $storeId, 'issueYear' => $year])->fetchPairs('number', 'id');
        $createdAt = DateTimeImmutable::createFromFormat(DateTime::DB_DATETIME, '2021-01-01 00:00:00');

        while ($line !== false) {
            $csvData = str_getcsv($line, ';');
            $number = (int) trim($csvData[0] ?? '');

            if (isset($savedNumbers[$number])) {
                $duplicities[] = $number;
                $line = strtok($separator);
                continue;
            }

            $date = DateTimeImmutable::createFromFormat(DateTime::CZ_DATE, trim($csvData[1] ?? ''));

            if (!$date || !$number) {
                throwDebug(new InvalidArgumentException('neplatný formát dat ' . $line));
                break;
            }

            $deliveryItem = new DeliveryItem();
            $deliveryItem->store = $store;
            $deliveryItem->number = $number;
            $deliveryItem->issueYear = $year;
            $deliveryItem->createdBy = $admin;
            $deliveryItem->createdAt = $createdAt;
            $deliveryItem->dispatchStart = $date;
            $deliveryItem->dispatchEnd = $date;

            $this->orm->deliveryItems->persist($deliveryItem);

            $line = strtok($separator);
        }

        if ($duplicities) {
            file_put_contents(LOG_DIR . '/duplicity.txt', implode(PHP_EOL, $duplicities));
        }

        $this->orm->deliveryItems->flush();
        return '';
    }

    private function getDeliveryNoteState(DeliveryNoteImport $noteImport, int $deliveryState = null): ?int
    {
        if (!in_array($noteImport->movementType, [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_TRANSFER_OUT])) {
            // Stav se urcuje pouze u dokladu k expedici
            return null;
        }

        if ($noteImport->state === ' ') {
            return DeliveryNote::STATE_RESERVATION;
        }

        if ($noteImport->state === 'P') {
             return DeliveryNote::STATE_PREPARATION;
        }

        if ($noteImport->state !== 'X') {
            throw new InvalidArgumentException("Nevalidní stav '$noteImport->state' u expedovaného dokladu $noteImport->number");
        }

        switch ($deliveryState) {
            case DeliveryItem::STATE_PREPARED:
                return DeliveryNote::STATE_PREPARED;
            case DeliveryItem::STATE_OPEN_DISPATCH:
                return DeliveryNote::STATE_DISPATCHING;
            case DeliveryItem::STATE_LOADED:
                return DeliveryNote::STATE_DONE;
            default:
                return DeliveryNote::STATE_LOADING;
        }
    }

    /**
     * @param int $storeId
     * @param NoteItemImport[] $imports
     * @param int $season
     */
    private function importNoteItems(int $storeId, array $imports, int $season): void
    {
        $itemCounter = 0;
        $items = [];
        $serviceCounter = 0;
        $services = [];
        $notes = $this->orm->deliveryNotes->loadByCurrentSeason($storeId, $season);
        $localStoreVariants = $this->orm->stockVariants->loadStoreVariants($storeId);
        $storeOutlets = $this->orm->stockVariants->loadStoreOutlets($storeId);
        $deliveryServices = $this->orm->deliveryServices->findAll()->fetchPairs('regNumber', 'id');

        foreach ($imports as $importData) {
            $noteDataKey = $importData->noteNumber
                . BaseMapper::DATA_STRING_SEPARATOR
                . $importData->movementNumber
                . BaseMapper::DATA_STRING_SEPARATOR
                . $importData->date->format(DateTime::DB_DATE);

            if (!isset($notes[$noteDataKey])) {
                continue;
            }

            if ($importData->isService) {
                $regNumber = (int) $importData->itemRegNumber;

                if (!isset($deliveryServices[$regNumber])) {
                    $deliveryServices[$regNumber] = $this->createUnknownService($regNumber);
                }

                $serviceCounter++;
                $services[] = [
                    'note' => $notes[$noteDataKey],
                    'service' => $deliveryServices[$regNumber],
                    'amount' => $importData->amount,
                    'sell_price' => $importData->sellPrice,
                    'buy_price' => $importData->buyPrice,
                    'discount' => $importData->discount,
                    'tax' => $importData->tax
                ];

                if ($serviceCounter === 500) {
                    $this->orm->deliveryNoteServices->insertItems($services);
                    $serviceCounter = 0;
                    $services = [];
                }
            } else {
                $variantDataKey = $importData->itemRegNumber
                    . BaseMapper::DATA_STRING_SEPARATOR
                    . ($importData->supplement === ' ' ? '' : $importData->supplement);

                if (!isset($localStoreVariants[$variantDataKey])) {
                    // Udaje o variante polozky jsou smazane
                    $localStoreVariants[$variantDataKey] = $this->createUnknownVariant(
                        $storeId,
                        $importData->itemRegNumber,
                        $importData->supplement
                    );
                }

                $itemCounter++;
                $items[] = [
                    'note' => $notes[$noteDataKey],
                    'item' => $localStoreVariants[$variantDataKey],
                    'amount' => $importData->amount,
                    'sell_price' => $importData->sellPrice,
                    'buy_price' => $importData->buyPrice,
                    'discount' => $importData->discount,
                    'tax' => $importData->tax,
                    'outlet_type' => $storeOutlets[$variantDataKey] ?? null
                ];

                if ($itemCounter === 500) {
                    $this->orm->deliveryNoteItems->insertItems($items);
                    $itemCounter = 0;
                    $items = [];
                }
            }
        }

        if ($items) {
            $this->orm->deliveryNoteItems->insertItems($items);
        }

        if ($services) {
            $this->orm->deliveryNoteServices->insertItems($services);
        }
    }

    private function findParentNotes(int $storeId): void
    {
        // Opravy dokladu
        $collection = $this->orm->deliveryNotes->findBy([
            'store->id' => $storeId,
            'description~' => LikeExpression::startsWith('Oprava dokladu'),
            'parent->id' => null
        ]);

        foreach ($collection as $note) {
            $parentNumber = (int) trim(str_replace('Oprava dokladu č.', '', $note->description));
            $parentNote = $this->orm->deliveryNotes->getBy([
                'store->id' => $storeId,
                'number' => $parentNumber,
                'movementNumber' => $note->movementNumber,
                'date<=' => $note->date,
                'date>=' => $note->date->setDate((int) $note->date->format('Y'), 1, 1)
            ]);

            if (!$parentNote) {
                // Oprava dokladu z minuleho roku
                $parentNote = $this->orm->deliveryNotes->getBy([
                    'store->id' => $storeId,
                    'number' => $parentNumber,
                    'movementNumber' => $note->movementNumber,
                    'date<=' => $note->date,
                    'date>=' => $note->date->setDate(((int) $note->date->format('Y')) - 1, 1, 1)
                ]);
            }

            $note->parent = $parentNote;
            $note->getRepository()->persist($note);
        }

        $this->orm->deliveryNotes->flush();

        // Storna
        $collection = $this->orm->deliveryNotes->findBy([
            'store->id' => $storeId,
            'movementType' => DeliveryNote::TYPE_CANCEL,
            'parent->id' => null,
            'cancelNote!=' => null
        ]);

        $logFile = DATA_DIR . "/$storeId/nezname-storna.csv";
        $logs = file_exists($logFile) ? file_get_contents($logFile) : '';

        foreach ($collection as $note) {
            $parentNote = $this->orm->deliveryNotes->getBy([
                'store->id' => $storeId,
                'number' => $note->cancelNote,
                'movementType' => DeliveryNote::TYPE_SALE,
                'date<=' => $note->date,
                'date>=' => $note->date->setDate((int) $note->date->format('Y'), 1, 1)
            ]);

            if (!$parentNote) {
                $parentNote = $this->orm->deliveryNotes->findBy([
                    'store->id' => $storeId,
                    'number' => $note->cancelNote,
                    'movementType' => DeliveryNote::TYPE_SALE,
                    'date<=' => $note->date,
                    'description' => $note->description
                ])->orderBy('date', ICollection::DESC)->fetch();
            }

            if ($parentNote) {
                $note->parent = $parentNote;
                $note->getRepository()->persist($note);
            } else {
                // Zaznam o neexistujícím dokladu ke stornu
                $line = "$note->number;$note->description;";
                $line .= $note->date->format(DateTime::CZ_DATE) . ";$note->cancelNote" . PHP_EOL;

                if (!Strings::contains($logs, $line)) {
                    file_put_contents($logFile, $line, FILE_APPEND);
                }
            }
        }

        $this->orm->deliveryNotes->flush();
    }

    public function findTransferParents(): string
    {
        // Prevodky
        $collection = $this->orm->deliveryNotes->findBy([
            'movementType' => DeliveryNote::TYPE_TRANSFER_IN,
            'description!=' => 'Stavba', // Stavba, nema protikus prevodky
            'parent->id' => null,
            'cancelNote' => null
        ]);

        $counter = 0;
        $logNotes = [];

        foreach ($collection as $note) {
            if ($counter === 2000) {
                $counter = null;
                break;
            }

            $counter++;
            $parentNote = $this->orm->deliveryNotes->getBy([
                'store->id' => (int) $note->depot->voj,
                'number' => (int) $note->depotNote,
                'movementType' => DeliveryNote::TYPE_TRANSFER_OUT,
                'date<=' => $note->date->modify('+7 days'), // nekdy prijmou zbozi drive nez ho vyskladni na jine pobocce
                'date>=' => $note->date->setDate((int) $note->date->format('Y'), 1, 1),
                'cancelNote' => null
            ]);

            if ($parentNote) {
                $note->parent = $parentNote;
                $note->getRepository()->persist($note);
            } else {
                $logNotes[] = $note;
            }
        }

        $this->orm->deliveryNotes->flush();

        if (is_null($counter)) {
            return "Párování převodek proběhlo pouze částečně";
        }

        if ($logNotes) {
            $this->logTransfers($logNotes);
        }

        return "Párování převodek proběhlo úspěšně";
    }

    /** Vytvori entitu pro partnera, pokud je v MOBce smazana */
    public function createUnknownDepot(int $storeId, int $ico, string $voj): int
    {
        $company = $this->orm->companies->getBy(['ico' => $ico]);

        if (!$company) {
            $company = $this->orm->companies->insertEntity(null, [
                'ico' => $ico,
                'name' => '???',
                'information' => [],
                'countryCode' => 'CZ'
            ]);
        }

        $depot = $this->orm->companyDepots->insertEntity(null, [
            'store' => $storeId,
            'company' => $company->id,
            'voj' => $voj,
            'title' => '',
            'city' => '???'
        ]);

        return $depot->id;
    }

    /** Vytvori entitu pro variantu zbozi na pobocce, pokud je v MOBce smazana */
    private function createUnknownVariant(int $storeId, string $regNumber, string $supplement): int
    {
        $item = $this->orm->stockItems->getBy(['regNumber' => $regNumber]);

        if (!$item) {
            $item = $this->orm->stockItems->insertEntity(null, [
                'regNumber' => $regNumber,
                'name' => '???'
            ]);
        }

        $entity = $this->orm->stockVariants->insertEntity(null, [
            'item' => $item->id,
            'supplement' => $supplement,
            'store' => $storeId,
            'quantity' => 0,
            'sample' => false,
            'deleted' => true
        ]);

        return $entity->id;
    }

    /** Vytvori entitu pro sluzbu na pobocce, pokud jeste neexistuje */
    public function createUnknownService(int $regNumber): int
    {
        $entity = $this->orm->deliveryServices->insertEntity(null, [
            'regNumber' => $regNumber,
            'name' => '???',
            'group' => 1
        ]);

        return $entity->id;
    }

    /**
     * @param DeliveryNote[] $notes
     */
    private function logTransfers(array $notes): void
    {
        $log = [];

        foreach ($notes as $note) {
            $log[$note->id] = [
                'store' => $note->store->id,
                'number' => $note->number,
                'description' => $note->description,
                'season' => $note->season,
                'movement' => $note->movementNumber,
                'depot' => $note->depot->voj,
                'note' => $note->depotNote
            ];
        }

        file_put_contents(DATA_DIR . '/unpaired-transfers.json', json_encode($log, JSON_PRETTY_PRINT));
    }

    public static function getNextMovementsImport(int $storeId, \DateTimeImmutable $lastImport): \DateTimeImmutable
    {
        // Importy pohybu jsou nastaveny na kazdych 15 min, prvni aktualni data jsou v 7. minute
        $lastImportAt = (int) $lastImport->format('i');
        $nextImportAt = $storeId < 9
            ? ($lastImportAt + $storeId + 19)
            : ($lastImportAt + $storeId + 9);

        if ($nextImportAt >= 60) {
            $nextImportAt -= 60;
            $lastImport = $lastImport->modify('+1 hour');
        }

        return $lastImport->setTime((int) $lastImport->format('G'), $nextImportAt);
    }
}
