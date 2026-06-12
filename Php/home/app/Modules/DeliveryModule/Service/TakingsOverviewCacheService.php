<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Service;

use App\Core\Utils\DateTime;
use App\Modules\DeliveryModule\Component\Entity\SalesFilterEntity;
use App\Modules\DeliveryModule\Component\TakingsOverview;
use App\Modules\DeliveryModule\Orm\TakingsOverviewCache\TakingsOverviewCache;
use App\Modules\StockModule\Orm\CustomGroups\CustomGroup;
use App\Modules\StockModule\Orm\Producers\Producer;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Service\OrmModel;

class TakingsOverviewCacheService
{
    private OrmModel $orm;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
    }

    public function setYearCache(int $year, int $type = TakingsOverviewCache::TYPE_STORE_TAKINGS, int $store = 0): void
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');

        if ($currentYear < $year) {
            return;
        }

        for ($month = 1; $month <= 12; $month++) {
            if ($currentYear === $year && $currentMonth === $month) {
                break;
            }

            $type === TakingsOverviewCache::TYPE_STORE_TAKINGS
                ? $this->setMonthCache($year, $month)
                : $this->setStoreSellsMonthCache($year, $month, $store);
        }
    }

    //analýza nákupu
    public function setMonthCache(int $year, int $month): void
    {
        $collection = $this->orm->takingsOverviewCache->findBy([
            'year' => $year,
            'month' => $month,
            'type' => TakingsOverviewCache::TYPE_STORE_TAKINGS
        ]);

        foreach ($collection as $unitCache) {
            $this->orm->takingsOverviewCache->remove($unitCache);
        }
        $this->orm->takingsOverviewCache->flush();

        foreach ($this->orm->customGroups->findAll() as $customGroup) { //1-Obklady a dlažby,2-Sanita,3-Nářadí a chemie
            $producers = array_keys($customGroup->loadProducers()); //výrobci skupiny zboží
            $stockGroups = $customGroup->stockGroups->getRawValue(); // podskupiny

            if ($customGroup->viewType === CustomGroup::VIEW_TYPE_TAKINGS_SUM) { //VIEW_TYPE_TAKINGS_SUM => 1-obklady a dlažby
                foreach ($this->loadSumTakings($year, $month, $producers, $stockGroups) as $producer => $data) {
                    $unitCache = new TakingsOverviewCache();
                    $unitCache->store = 10; // Do Hlucina se uklada cache z nakupu m2
                    $unitCache->group = $customGroup->id;
                    $unitCache->producer = $producer;
                    $unitCache->year = $year;
                    $unitCache->month = $month;
                    $unitCache->value = round($data[TakingsOverview::VIEW_BY_UNIT]);

                    $priceCache = new TakingsOverviewCache();
                    $priceCache->store = 9; // Do Michalokovic se uklada cache z nakupu Kc
                    $priceCache->group = $customGroup->id;
                    $priceCache->producer = $producer;
                    $priceCache->year = $year;
                    $priceCache->month = $month;
                    $priceCache->value = round($data[TakingsOverview::VIEW_BY_PRICE]);

                    $this->orm->takingsOverviewCache->persist($unitCache);
                    $this->orm->takingsOverviewCache->persist($priceCache);
                }
            } else {//VIEW_TYPE_TAKINGS_SUM => 2-sanita/nářadí a chemie
                foreach ($this->loadStoreTakings($year, $month, $producers, $stockGroups) as $producer => $storeData) {
                    foreach ($storeData as $store => $value) {
                        $priceCache = new TakingsOverviewCache();
                        $priceCache->store = $store;
                        $priceCache->group = $customGroup->id;
                        $priceCache->producer = $producer;
                        $priceCache->year = $year;
                        $priceCache->month = $month;
                        $priceCache->value = round($value);

                        $this->orm->takingsOverviewCache->persist($priceCache);
                    }
                }
            }
        }

        $this->orm->takingsOverviewCache->flush();
    }

    //Analýza odběrů poboček
    public function setStoreSellsMonthCache(int $year, int $month, int $store = 0): void
    {
        if($year >= 2025){
            $this->orm->takingsOverviewCache->getMapper()->updateCacheTakingsOverview2025($year, $month);
            return;
        }

        if ($store) {
            $stores = [$store];
        } else {
            $stores = $this->orm->stores->loadSimpleStoreIds();
            $stores[] = Store::OSTRAVA_MAIN_STORAGE;
        }

        $collection = $this->orm->takingsOverviewCache->findBy([
            'year' => $year,
            'month' => $month,
            'type' => TakingsOverviewCache::TYPE_STORE_SELLS,
            'store' => $stores
        ]);

        foreach ($collection as $unitCache) {
            $this->orm->takingsOverviewCache->remove($unitCache);
        }
        $this->orm->takingsOverviewCache->flush();

        //foreach ($this->orm->customGroups->findAll() as $customGroup) {
        //    $emptyArray = [];
        //    $producers = array_keys($customGroup->loadProducers(true));
        //    foreach ($producers as $producerId) {
        //        $filter = new SalesFilterEntity($this->orm, 1, [], [$year], 0, [$producerId], $customGroup);
        //        $groups = $filter->getStockGroupFilter($producerId);
        //        $emptyArray = array_merge($emptyArray, $groups);
        //    }
        //}

        foreach ($this->orm->customGroups->findAll() as $customGroup) {
            $producers = array_keys($customGroup->loadProducers(true));

            foreach ($stores as $storeId) {
                $ravak = new TakingsOverviewCache();
                foreach ($producers as $producerId) {
                    $filter = new SalesFilterEntity($this->orm, $storeId, [], [$year], 0, [$producerId], $customGroup);
                    $priceCache = new TakingsOverviewCache();
                    $priceCache->type = TakingsOverviewCache::TYPE_STORE_SELLS;
                    $priceCache->store = $storeId;
                    $priceCache->group = $customGroup->id;
                    $priceCache->producer = $producerId;
                    $priceCache->year = $year;
                    $priceCache->month = $month;
                    $priceCache->value = $this->orm->salesData->loadStoreFilterSalesSum(
                        $filter,
                        $filter->getStockGroupFilter($producerId),
                        $year,
                        $month
                    );

                    if ($producerId == 20) {
                        $ravak = $priceCache;
                    }

                    if ($producerId == Producer::DC_RAVAK_ID) {
                        $ravak->value = $ravak->value - $priceCache->value;
                        $this->orm->takingsOverviewCache->persist($ravak);
                    }

                    $this->orm->takingsOverviewCache->persist($priceCache);
                }
            }
        }

        $this->orm->takingsOverviewCache->flush();
    }

    /** Nahraje udaje o nakupech za zvoleny mesic a skupinu zbozi */
    public function loadSumTakings(int $year, int $month, array $producerIds, array $stockGroups): array
    {
        $takings = [];
        $producerParents = $this->orm->producers->findBy(['parent->id!=' => null])->fetchPairs('id', 'parent->id');
        $span = self::prepareMonthSpans($year)[$month];

        $priceData = $this->orm->deliveryNoteItems->loadTakingsPriceData($stockGroups, $span['start'], $span['end']);
        $unitData = $this->orm->deliveryNoteItems->loadSquareMetersTakingsData($stockGroups, $span['start'], $span['end']);
        $emptyPaletteData = $this->orm->deliveryNoteItems->loadEmptyPalettesTakingsData($span['start'], $span['end']);

        foreach ($producerIds as $producerId) {
            $sumId = $producerParents[$producerId] ?? $producerId;

            if (!isset($takings[$sumId][TakingsOverview::VIEW_BY_PRICE])) {
                $takings[$sumId][TakingsOverview::VIEW_BY_PRICE] = 0;
                $takings[$sumId][TakingsOverview::VIEW_BY_UNIT] = 0;
            }

            $takings[$sumId][TakingsOverview::VIEW_BY_PRICE] += $priceData[$producerId] ?? 0;
            $takings[$sumId][TakingsOverview::VIEW_BY_PRICE] += $emptyPaletteData[$producerId] ?? 0;
            $takings[$sumId][TakingsOverview::VIEW_BY_UNIT] += $unitData[$producerId] ?? 0;
        }

        return $takings;
    }

    /** Nahraje udaje o nakupech za zvoleny rok a skupinu zbozi po jednotlivych pobockach */
    //analýza nákupu
    public function loadStoreTakings(int $year, int $month, array $producerIds, array $stockGroups): array
    {
        $takings = [];
        $producerParents = $this->orm->producers->findBy(['parent->id!=' => null])->fetchPairs('id', 'parent->id'); //Zalakerámia -> Lasselsberger, ARTE -> Tubadzin
        $span = self::prepareMonthSpans($year)[$month]; // interval pracovního měsíce
        $stores = $this->orm->stores->findAll()->fetchPairs('id', 'id'); //1-11
        // Do statistik se data velkoskladu scitaji
        unset($stores[Store::HLUCIN_MAIN_STORAGE]); // odstraní se Hlučín

        $sumData = $this->orm->deliveryNoteItems->loadStoreTakingsData($stockGroups, $span['start'], $span['end']);

        foreach ($stores as $storeId) {
            foreach ($producerIds as $producerId) {
                $sumId = $producerParents[$producerId] ?? $producerId;

                if (!isset($takings[$month][$sumId][$storeId])) {
                    $takings[$sumId][$storeId] = 0;
                }

                $takings[$sumId][$storeId] += $sumData[$producerId][$storeId] ?? 0;
            }
        }

        $ravakId = $this->orm->producers->getBy(['name' => Producer::RAVAK_NAME])->id ?? 0;

        if (in_array($ravakId, $producerIds)) {
            // DC Ravak hack
            $dcRavakGroups = $this->orm->stockGroups->findDcRavakGroups()->fetchPairs(null, 'id');
            $sumData = $this->orm->deliveryNoteItems->loadStoreTakingsData($dcRavakGroups, $span['start'], $span['end']);

            foreach ($stores as $storeId) {
                $takings[Producer::DC_RAVAK_ID][$storeId] = $sumData[$ravakId][$storeId] ?? 0;
                $takings[$ravakId][$storeId] = $takings[$ravakId][$storeId] - $takings[Producer::DC_RAVAK_ID][$storeId];
            }
        }

        return $takings;
    }

    /** Pripravi rozsahy prijmovych mesicu pro zvoleny rok - prijmovy mesic konci posledni pracovni den v dalsim mesici */
    public static function prepareMonthSpans(int $year): array
    {
        $date = DateTime::createFromFormat(DateTime::DB_DATETIME, "$year-01-01 00:00:00");
        $monthSpans = [];

        for ($month = 1; $month <= 12; $month++) {
            $span = [];

            if ($month !== 1) {
                $date->modify('+1 day');
            }

            $span['start'] = clone $date;

            if ($month === 12) {
                $span['end'] = DateTime::createFromFormat(DateTime::DB_DATETIME, "$year-12-31 23:59:00");
            } else {
                $date->modify('first day of next month');

                while (!$date->isWorkingDay()) {
                    $date->modify('+1 day');
                }

                $span['end'] = (clone $date)->setTime(23, 59);
            }

            $monthSpans[$month] = $span;
        }

        return $monthSpans;
    }
}
