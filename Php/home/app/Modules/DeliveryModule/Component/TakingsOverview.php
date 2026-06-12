<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Component;

use App\Modules\DeliveryModule\Service\OverviewExporter;
use App\Modules\StockModule\Orm\CustomGroups\CustomGroup;
use App\Modules\StockModule\Orm\Producers\Producer;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Service\OrmModel;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Http\SessionSection;

class TakingsOverview extends Control
{
    public const VIEW_BY_UNIT = 1;
    public const VIEW_BY_PRICE = 2;

    private OrmModel $orm;
    private OverviewExporter $exporter;
    private SessionSection $session;
    public int $selectedYear;
    public ?int $selectedGroup;
    public array $selectedProducers;
    public bool $selectAllProducers = false;
    public array $selectedStores;
    public bool $showDifference;
    public int $viewType;
    public bool $totalSumView;
    public int $graphViewType;
    public int $graphYearFrom;
    public int $graphYearTill;

    public function __construct(OrmModel $orm, OverviewExporter $exporter)
    {
        $this->orm = $orm;
        $this->exporter = $exporter;
        $this->monitor(Presenter::class, function (): void {
            $this->session = $this->getPresenter()->getSession('takingsOverview');
        });
    }

    public function loadState(array $params): void
    {
        $this->selectedYear = $this->session->selectedYear ?? (int)date('Y');
        $this->selectedGroup = $this->session->selectedGroup ?? $this->orm->customGroups->findAll()->fetch()->id ?? null;
        $this->selectedProducers = $this->session->selectedGroupProducers[$this->selectedGroup] ?? [];
        $this->showDifference = $this->session->showDifference ?? false;
        $this->totalSumView = $this->selectedYear === 0;
        $this->graphViewType = $this->session->graphViewType ?? self::VIEW_BY_PRICE;

        if ($this->selectedGroup === 1 && empty($this->session->selectedGroupProducers)) {
            $this->selectAllProducers = true;
        }

        $this->graphYearTill = $this->session->graphYearTill ?? (intval(date('Y')) - 1);
        $this->graphYearFrom = $this->session->graphYearFrom ?? 2011;
        $this->selectedStores = $this->session->selectedStores ?? array_keys($this->getAllStores());

        if (empty($this->session->graphYearFrom) && $this->selectedGroup === 1) {
            $this->graphYearFrom = $this->graphViewType === self::VIEW_BY_PRICE ? 2006 : 2003;
        }
    }

    /** Nastaveni roku - pokud neni nastaven rok, jedna se o souhrn vsech let se zobrazenim grafu */
    public function handleSetYear(): void
    {
        $this->selectedYear = $this->session->selectedYear = (int) $this->getPresenter()->getParameter('year');
        $this->totalSumView = $this->selectedYear === 0;
        if ($this->selectedGroup !== 1 && $this->totalSumView && $this->graphYearFrom < 2011) {
            $this->graphYearFrom = $this->session->graphYearFrom = 2011;
        }
        $this->redrawControl('takingsOverview');
    }

    /** Nastaveni zobrazene skupiny zbozi (kachle, sanita, chemie) */
    public function handleSetGroup(): void
    {
        $this->selectedGroup = $this->session->selectedGroup = (int) $this->getPresenter()->getParameter('group');
        $this->selectedProducers = $this->session->selectedGroupProducers[$this->selectedGroup] ?? [];

        if ($this->selectedGroup !== 1 && !$this->totalSumView && $this->selectedYear < 2011) {
            $this->selectedYear = $this->session->selectedYear = (int) date('Y');
        }

        if ($this->selectedGroup !== 1 && $this->totalSumView && $this->graphYearFrom < 2011) {
            $this->graphYearFrom = $this->session->graphYearFrom = 2011;
        }

        $this->redrawControl('takingsOverview');
    }

    /** Nastaveni filtrace zobrazovanych vyrobcu */
    public function handleSetProducers(): void
    {
        $this->selectedProducers = $this->getPresenter()->getParameter('producers') ?? [];

        if (in_array(0, $this->selectedProducers)) {
            $this->selectAllProducers = true;
        }

        $this->session->selectedGroupProducers[$this->selectedGroup] = $this->selectedProducers;
        $this->redrawControl('takingsOverview');
    }

    /** Nastaveni filtrace zobrazovanych prodejen */
    public function handleSetStores(): void
    {
        $selectedStores = $this->getPresenter()->getParameter('stores') ?? [];
        $this->selectedStores = in_array(0, $selectedStores) ? array_keys($this->getAllStores()) : $selectedStores;
        $this->session->selectedStores = $this->selectedStores;
        $this->redrawControl('takingsOverview');
    }

    /** Zapinani zobrazeni srovnani s minulym rokem */
    public function handleShowDifference(): void
    {
        $this->showDifference = $this->session->showDifference = (bool) $this->getPresenter()->getParameter('showDifference');
        $this->redrawControl('takingsOverview');
    }

    /** Prepinani zobraeni grafu (kc nebo m2 za rok) */
    public function handleSetGraphViewType(): void
    {
        $this->graphViewType = $this->session->graphViewType = (int) $this->getPresenter()->getParameter('graphViewType');
        if ($this->graphViewType === self::VIEW_BY_PRICE && $this->graphYearFrom < 2006) {
            $this->graphYearFrom = $this->session->graphYearFrom = 2006;
        }
        $this->redrawControl('takingsOverview');
    }

    /** Nastaveni rozsahu dat pro graf */
    public function handleSetGraphYearFrom(): void
    {
        $yearFrom = (int) $this->getPresenter()->getParameter('graphYearFrom');
        if ($yearFrom < $this->graphYearTill) {
            $this->graphYearFrom = $this->session->graphYearFrom = $yearFrom;
            $this->redrawControl('takingsOverview');
        }
    }

    /** Nastaveni rozsahu dat pro graf */
    public function handleSetGraphYearTill(): void
    {
        $yearTill = (int) $this->getPresenter()->getParameter('graphYearTill');
        if ($yearTill > $this->graphYearFrom) {
            $this->graphYearTill = $this->session->graphYearTill = $yearTill;
            $this->redrawControl('takingsOverview');
        }
    }

    /** Export zobrazenych dat do excelu */
    public function handleExcelExport(): void
    {
        $customGroup = $this->orm->customGroups->getById($this->selectedGroup);
        $viewType = $customGroup->viewType ?? CustomGroup::VIEW_TYPE_TAKINGS_SUM;
        $producers = $this->orm->producers->findBy(['id' => $this->selectedProducers])->orderBy('number')
            ->fetchPairs('id', 'name');

        if ($viewType === CustomGroup::VIEW_TYPE_TAKINGS_SUM && $this->totalSumView) {
            $takings = $this->orm->takingsOverviewCache->loadTotalSumTakingsData()[$this->graphViewType] ?? [];
            $heading = $this->graphViewType === self::VIEW_BY_PRICE ? 'Kč/rok' : 'Odebrané množství m2/rok';
            $response = $this->exporter->totalTakingsToExcel($heading, $producers, $takings);
        } elseif ($viewType === CustomGroup::VIEW_TYPE_TAKINGS_SUM) {
            $mop = $this->orm->takingsOverviewCache->loadSumTakingsData($this->selectedYear);
            $lastMop = $this->showDifference
                ? $this->orm->takingsOverviewCache->loadSumTakingsData($this->selectedYear - 1) : null;
            $k2 = $this->orm->takingsOverviewCacheK2->loadSumTakingsData($this->selectedYear);
            $lastK2 = $this->showDifference
                ? $this->orm->takingsOverviewCacheK2->loadSumTakingsData($this->selectedYear - 1) : null;
            $takings = $this->sumMopK2($mop, $k2);
            $lastTakings = $lastMop;
            if ($lastMop != null && $lastK2 != null){
                $lastTakings = $this->sumMopK2($lastMop, $lastK2);
            }
            $response = $this->exporter->takingsToExcel($this->selectedYear, $producers, $takings, $lastTakings);
        } elseif ($this->totalSumView) {
            $takings = $this->orm->takingsOverviewCache->loadTotalStoreTakingsData($this->selectedGroup);
            $response = $this->exporter->totalStoreTakingsToExcel($customGroup->name ?? '', $this->selectedProducers, $this->selectedStores, $takings);
        } else {
            //$takings = $this->orm->takingsOverviewCache->loadStoreTakingsData($this->selectedYear, $this->selectedGroup);
            //$lastTakings = $this->showDifference
            //    ? $this->orm->takingsOverviewCache->loadStoreTakingsData($this->selectedYear - 1, $this->selectedGroup) : null;
            //$response = $this->exporter->storeTakingsToExcel($customGroup->name ?? '', $this->selectedYear, $this->selectedProducers, $this->selectedStores, $takings, $lastTakings);

            $mop = $this->orm->takingsOverviewCache->loadStoreTakingsData($this->selectedYear, $this->selectedGroup);
            $k2 = $this->orm->takingsOverviewCacheK2->loadStoreTakingsData($this->selectedYear, $this->selectedGroup);
            $takings = $this->sumMopK2($mop, $k2);

            if ($this->showDifference){
                $mop = $this->orm->takingsOverviewCache->loadStoreTakingsData($this->selectedYear, $this->selectedGroup);
                $k2 = $this->orm->takingsOverviewCacheK2->loadStoreTakingsData($this->selectedYear, $this->selectedGroup);
                $lastTakings = $this->sumMopK2($mop, $k2);
            }
            else{
                $lastTakings = null;
            }

            $response = $this->exporter->storeTakingsToExcel($customGroup->name ?? '', $this->selectedYear, $this->selectedProducers, $this->selectedStores, $takings, $lastTakings);

        }

        if ($response) {
            $this->getPresenter()->sendResponse($response);
        } else {
            $this->redrawControl('takingsOverview');
        }
    }

    private function sumMopK2(array $data1, array $data2):array
    {
        $sums = [];

        foreach ($data1 as $key => $subarray1) {
            foreach ($subarray1 as $subkey => $values1) {
                foreach ($values1 as $innerkey => $value1) {
                    if (!isset($sums[$key][$subkey][$innerkey])) {
                        $sums[$key][$subkey][$innerkey] = 0;
                    }
                    $sums[$key][$subkey][$innerkey] += $value1;
                }
            }
        }

        foreach ($data2 as $key => $subarray2) {
            foreach ($subarray2 as $subkey => $values2) {
                foreach ($values2 as $innerkey => $value2) {
                    if (!isset($sums[$key][$subkey][$innerkey])) {
                        $sums[$key][$subkey][$innerkey] = 0;
                    }
                    $sums[$key][$subkey][$innerkey] += $value2;
                }
            }
        }

        return $sums;
        /*for ($i = 1; $i <= 2; $i++) { //$i je tento a loňský rok
            if (array_key_exists($i, $mop) and array_key_exists($i, $k2)){
                for ($j = 1; $j <= 12; $j++) { //$j 12 měsíců
                    if (array_key_exists($j, $mop[$i]) and array_key_exists($j, $k2[$i])) { // jestli je stejný měsíc v obou polích
                        foreach ($mop[$i][$j] as $mopItem => $item){ // pro všechny výrobce v mop
                            if (array_key_exists($mopItem, $k2[$i][$j])){ // jestli existuje mop výrobce v k2
                                $mop[$i][$j][$mopItem] = $item + $k2[$i][$j][$mopItem];
                            }
                        }
                    }
                }
            }
        }
        return $mop;*/
    }

    public function render(): void
    {
        $customGroup = $this->orm->customGroups->getById($this->selectedGroup);
        $this->viewType = $customGroup->viewType ?? CustomGroup::VIEW_TYPE_TAKINGS_SUM;

        if ($this->totalSumView) {
            $mop = $this->viewType === CustomGroup::VIEW_TYPE_TAKINGS_SUM
                ? ($this->orm->takingsOverviewCache->loadTotalSumTakingsData()[$this->graphViewType] ?? [])
                : $this->orm->takingsOverviewCache->loadTotalStoreTakingsData($this->selectedGroup);
            $k2 = $this->viewType === CustomGroup::VIEW_TYPE_TAKINGS_SUM
                ? ($this->orm->takingsOverviewCacheK2->loadTotalSumTakingsData()[$this->graphViewType] ?? [])
                : $this->orm->takingsOverviewCacheK2->loadTotalStoreTakingsData($this->selectedGroup);
            $takings = $this->sumMopK2($mop, $k2);
        } else {
            $mop = $this->viewType === CustomGroup::VIEW_TYPE_TAKINGS_SUM
                ? $this->orm->takingsOverviewCache->loadSumTakingsData($this->selectedYear)
                : $this->orm->takingsOverviewCache->loadStoreTakingsData($this->selectedYear, $this->selectedGroup);
            $k2 = $this->viewType === CustomGroup::VIEW_TYPE_TAKINGS_SUM
                ? $this->orm->takingsOverviewCacheK2->loadSumTakingsData($this->selectedYear)
                : $this->orm->takingsOverviewCacheK2->loadStoreTakingsData($this->selectedYear, $this->selectedGroup);
            $takings = $this->sumMopK2($mop, $k2);

            $mop = $this->viewType === CustomGroup::VIEW_TYPE_TAKINGS_SUM
                ? $this->orm->takingsOverviewCache->loadSumTakingsData($this->selectedYear - 1)
                : $this->orm->takingsOverviewCache->loadStoreTakingsData($this->selectedYear - 1, $this->selectedGroup);
            $k2 = $this->viewType === CustomGroup::VIEW_TYPE_TAKINGS_SUM
                ? $this->orm->takingsOverviewCacheK2->loadSumTakingsData($this->selectedYear - 1)
                : $this->orm->takingsOverviewCacheK2->loadStoreTakingsData($this->selectedYear - 1, $this->selectedGroup);
            $lastTakings = $this->sumMopK2($mop, $k2);
        }

        if ($this->viewType === CustomGroup::VIEW_TYPE_STORE_TAKINGS) {
            $this->template->stores = $this->getAllStores();

            if ($this->totalSumView) {
                $storeColors = $this->orm->stores->findAll()->fetchPairs('id', 'color');
                unset($storeColors[Store::HLUCIN_MAIN_STORAGE]);
                $storeColors[0] = $storeColors[Store::OSTRAVA_MAIN_STORAGE];
                unset($storeColors[Store::OSTRAVA_MAIN_STORAGE]);
                ksort($storeColors);
                $this->template->storeColors = array_values($storeColors);
            }
        }

        $this->template->takings = $takings;
        $this->template->lastTakings = $lastTakings ?? [];

        $producers = [];
        $producerColors = [];

        foreach ($this->orm->producers->findBy(['id' => $this->getProducersToDisplay($takings)])->orderBy('number') as $producer) {
            $producers[$producer->id] = $producer->name;
            $producerColors[$producer->id] = $producer->color;
            // DC Ravak hack
            if ($producer->name === Producer::RAVAK_NAME) {
                $producers[Producer::DC_RAVAK_ID] = 'DC Ravak';
                $producerColors[Producer::DC_RAVAK_ID] = '#d3041b';
            }
        }

        if ($this->selectAllProducers) {
            $this->selectedProducers = $this->session->selectedGroupProducers[$this->selectedGroup] = array_keys($producers);
        }

        $this->template->producers = $producers;
        $this->template->producerColors = $producerColors;
        $this->template->groups = $this->orm->customGroups->findAll()->fetchPairs('id', 'name');
        $this->template->setFile(__DIR__ . '/templates/takingsOverview.latte');
        $this->template->render();
    }

    private function getProducersToDisplay(array $takings): array
    {
        if (isset($takings['sum'])) {
            return array_keys($takings['sum']);
        }
        if (isset($takings[self::VIEW_BY_UNIT])) {
            $takings = $takings[self::VIEW_BY_UNIT];
        }
        if (isset($takings[2011])) {
            return array_keys($takings[2011]);
        }
        //return array_keys(reset($takings));
        $uniqueKeys = [];

        foreach ($takings as $firstLevel) {
            foreach ($firstLevel as $key => $value) {
                $uniqueKeys[$key] = true; // Store unique keys
            }
        }

        return array_keys($uniqueKeys);
    }

    private function getAllStores(): array
    {
        $stores = $this->orm->stores->findAll()->fetchPairs('id', 'name');
        unset($stores[Store::OSTRAVA_MAIN_STORAGE], $stores[Store::HLUCIN_MAIN_STORAGE], $stores[Store::LC_MAIN_STORAGE]);
        return [9 => 'Velkoobchod'] + $stores;
    }
}
