<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Component;

use App\Modules\DeliveryModule\Orm\TakingsOverviewCache\TakingsOverviewCache;
//use PDO;
//use PDOException;

class StoreOverview extends BaseOverview
{
    private array $simpleStoreFilterCache;

    protected function createComponentOverviewFilter(): StoreOverviewFilter
    {
        return new StoreOverviewFilter($this->orm);
    }

    //podle jednotlivých výrobců a ičo, všechny nebo jednotlivé pobočky
    public function loadCellValue(int $producer, int $year, int $month): int
    {
        $returnK2 = 0;
        if (!$this->filter->isSimpleStoreFilter()){
            $returnK2 = $this->orm->salesData->loadStoreFilterSalesSumK2(
                $this->filter,
                $year,
                $month,
                $producer,
                null
            );
        }
        $return = $this->filter->isSimpleStoreFilter()
            ? $this->getSimpleStoreFilterValue($producer, $year, $month)
            : $this->orm->salesData->loadStoreFilterSalesSum(
                $this->filter,
                $this->filter->getStockGroupFilter($producer),
                $year,
                $month
            );
        return $return + $returnK2;
    }

    //analýza ico
    //podle jednotlivých skupin a ičo, všechny nebo jednotlivé pobočky
    public function loadCellTotalValue(int $stockGroup, int $year, int $month): int
    {
        $returnK2 = 0;
        if (!$this->filter->isSimpleStoreFilter()){
            $returnK2 = $this->orm->salesData->loadStoreFilterSalesSumK2(
                $this->filter,
                $year,
                $month,
                null,
                $stockGroup
            );
        }

        $return = $this->filter->isSimpleStoreFilter()
            ? $this->getSimpleStoreFilterTotalValue($stockGroup, $year, $month)
            : $this->orm->salesData->loadStoreFilterSalesSum(
                $this->filter,
                $this->getStockGroups($stockGroup),
                $year,
                $month
            );

        return $return + $returnK2;
    }

    //pro jednotlivou pobočku podle výrobce bez ičo
    private function getSimpleStoreFilterValue(int $producer, int $year, int $month): int
    {
        /*$serverName = "95.173.83.115";
        $connectionOptions = array(
            "Database" => "JasK2Db",
            "Uid" => "sa",
            "PWD" => "Perft1535"
        );

        try {
            $conn = new PDO("sqlsrv:Server=$serverName;Database=" . $connectionOptions['Database'], $connectionOptions['Uid'], $connectionOptions['PWD']);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }

        $tsql = "SELECT top 10 * FROM TSalesItemBookDM";

        $getProducts = $conn->prepare($tsql);
        $getProducts->execute();
        $products = $getProducts->fetchAll(PDO::FETCH_ASSOC);
        $productCount = count($products);*/

        if (!isset($this->simpleStoreFilterCache)) {
            $this->simpleStoreFilterCache = [];
            $collection = $this->orm->takingsOverviewCache->findBy([
                'type' => TakingsOverviewCache::TYPE_STORE_SELLS,
                'store' => $this->filter->store ?: $this->orm->stores->loadSimpleStoreIds(),
                'group' => $this->filter->stockGroup->id
            ]);

            $collection_k2 = $this->orm->takingsOverviewCacheK2->findBy([
                'type' => TakingsOverviewCache::TYPE_STORE_SELLS,
                'store' => $this->filter->store ?: $this->orm->stores->loadSimpleStoreIds(),
                'group' => $this->filter->stockGroup->id
            ]);

            foreach ($collection as $cacheData) {
                $this->simpleStoreFilterCache[$cacheData->producer][$cacheData->year][$cacheData->month] ??= 0;
                $this->simpleStoreFilterCache[$cacheData->producer][$cacheData->year][$cacheData->month] += $cacheData->value;
            }
            foreach ($collection_k2 as $cacheData) {
                $this->simpleStoreFilterCache[$cacheData->producer][$cacheData->year][$cacheData->month] ??= 0;
                $this->simpleStoreFilterCache[$cacheData->producer][$cacheData->year][$cacheData->month] += $cacheData->value;
            }
        }
        $return = $this->simpleStoreFilterCache[$producer][$year][$month] ?? 0;
        return $return;
    }

    //pro jednotlivou pobočku podle skupin bez ičo
    private function getSimpleStoreFilterTotalValue(int $stockGroup, int $year, int $month): int
    {
        $mop = array_sum($this->orm->takingsOverviewCache->findBy([
            'type' => TakingsOverviewCache::TYPE_STORE_SELLS,
            'store' => $this->filter->store ?: $this->orm->stores->loadSimpleStoreIds(),
            'group' => $stockGroup,
            'year' => $year,
            'month' => $month
        ])->fetchPairs(null, 'value'));
        $k2 = array_sum($this->orm->takingsOverviewCacheK2->findBy([
                'type' => TakingsOverviewCache::TYPE_STORE_SELLS,
                'store' => $this->filter->store ?: $this->orm->stores->loadSimpleStoreIds(),
                'group' => $stockGroup,
                'year' => $year,
                'month' => $month
            ])->fetchPairs(null, 'value'));
        if($year==2024 and $stockGroup == 1){
            return $mop + $k2;
        }
        return $mop + $k2;
    }
}
