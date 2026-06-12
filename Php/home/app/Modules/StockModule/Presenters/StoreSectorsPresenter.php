<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Presenters;

use App\Modules\CliModule\Service\MovementImporter;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\StockModule\Orm\StockItems\StockItem;
use Nextras\Orm\Collection\Expression\LikeExpression;
use Nextras\Orm\Collection\ICollection;

/** Presenter pro praci se sektory vybrane pobocky */
final class StoreSectorsPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Sektory zboží'
    ];

    /** AJAX odpoved na zadany text pro vyhledani sektoru pobocky */
    public function handleUpdateSearchSectors(): void
    {
        $search = trim($this->getParameter('search'));
        $items = [];
        $collections = [];
        $sectors = $this->orm->stockSectors->findBy([
            'store->id' => $this->selectedStore,
            'name~' => LikeExpression::startsWith($search)
        ]);

        if ($sectors->count()) {
            // Pokud se najde mnoho polozek, server pada kvuli omezeni pameti pro zpracovani pozadavku
            if ($sectors->count() > 100) {
                $this->template->searchError = 'Nalezeno příliš mnoho záznamů, prosím upřesněte zadávání';
                $this->redrawControl('searched-sectors');
                return;
            }

            foreach ($sectors as $sector) {
                $collections[] = $sector->variants->toCollection()->findBy(['deleted' => false]);
            }
        } else {
            $filter = [
                'query' => explode(' ', $search),
                'fulltextColumns' => ['regNumber', 'name', 'catalog'],
                'status' => 'all'
            ];

            $itemsCollection = $this->orm->stockItems->getDataForDatagrid($filter, []);

            // Pokud se najde mnoho polozek, server pada kvuli omezeni pameti pro zpracovani pozadavku
            if ($itemsCollection->count() > 100) {
                $this->template->searchError = 'Nalezeno příliš mnoho záznamů, prosím upřesněte zadávání';
                $this->redrawControl('searched-sectors');
                return;
            }

            /** @var StockItem $item */
            foreach ($this->orm->stockItems->getDataForDatagrid($filter, []) as $item) {
                $collections[] = $item->variants->toCollection()
                    ->findBy(['store->id' => $this->selectedStore, 'deleted' => false]);
            }
        }

        foreach ($collections as $collection) {
            foreach ($collection->orderBy('quantity', ICollection::DESC) as $variant) {
                $sector = $variant->sectorName ?? '';

                if (!isset($items[$variant->regNumber][$sector])) {
                    $items[$variant->regNumber][$sector] = [
                        'name' => $variant->name,
                        'unit' => $variant->unit,
                        'variants' => []
                    ];
                }

                $sideQuantity = $variant->loadSideQuantity();

                if ($variant->quantity || $sideQuantity) {
                    $reservationQuantity = $sideQuantity[DeliveryNote::STATE_RESERVATION] ?? 0;
                    $reservationQuantity += $sideQuantity[DeliveryNote::STATE_PREPARATION] ?? 0;
                    $loadingQuantity = $sideQuantity[DeliveryNote::STATE_LOADING] ?? 0;
                    $loadingQuantity += $sideQuantity[DeliveryNote::STATE_PREPARED] ?? 0;
                    $loadingQuantity += $sideQuantity[DeliveryNote::STATE_DISPATCHING] ?? 0;

                    $items[$variant->regNumber][$sector]['variants'][] = [
                        'name' => $variant->remark,
                        'catalog' => $variant->catalogTitle,
                        'quantity' => $variant->quantity,
                        'reservation' => $reservationQuantity,
                        'loading' => $loadingQuantity,
                        'sum' => $variant->quantity + $reservationQuantity + $loadingQuantity
                    ];
                }
            }
        }

        $this->template->items = $items;

        if ($items) {
            $storeName = $this->orm->stores->getById($this->selectedStore)->name;
            $import = $this->orm->imports->getImportByName("Pohyby zboží - $storeName");

            if ($import) {
                $this->template->importAt = $import->date;
                $this->template->nextImportAt = MovementImporter::getNextMovementsImport($this->selectedStore, $import->date);
            }
        } else {
            $this->template->searchError = 'Nenalezeny žádné položky';
        }

        $this->redrawControl('searched-sectors');
    }
}
