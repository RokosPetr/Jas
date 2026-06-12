<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\SalesData;

use App\Core\Orm\BaseMapper;
use App\Core\Utils\DateTime;
use App\Modules\DeliveryModule\Component\Entity\SalesFilterEntity;
use App\Modules\DeliveryModule\Component\StoreOverviewFilter;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Modules\SystemModule\Orm\Stores\StoreRepository;

class SalesDataMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_sales_data';
    public $count = 0;

    public function loadToDateInMonth(int $storeId, \DateTimeInterface $date, bool $withOpenSums = false): array
    {
        $result = [
            SalesData::RAW_SALES_DATA => 0,
            SalesData::RAW_PROFIT_DATA => 0,
            SalesData::OPEN_SALES_DATA => 0,
            SalesData::OPEN_PROFIT_DATA => 0
        ];
        $dataSets = [];
        $openSumSets = [];
        $year = $date->format('Y');
        $month = $date->format('m');
        $from = "$year-$month-01";
        $till = $date->format('Y-m-d');

        switch ($storeId) {
            case 90:
                $dataSets[] = $this->loadTotalNoteSumForMainStorages($from, $till);
                break;
            case 91:
                // Velkoobchod 1
                $dataSets[] = $this->loadNoteSumForMainStorage($from, $till, SalesDataRepository::MAIN_STORAGE_1_GROUPS);
                if ($withOpenSums) {
                    $openSumSets[] = $this->loadOpenSumForMainStorage($from, $till, SalesDataRepository::MAIN_STORAGE_1_GROUPS);
                }
                break;
            case 92:
                // Velkoobchod 2
                $dataSets[] = $this->loadNoteSumForMainStorage($from, $till, SalesDataRepository::MAIN_STORAGE_2_GROUPS);
                if ($withOpenSums) {
                    $openSumSets[] = $this->loadOpenSumForMainStorage($from, $till, SalesDataRepository::MAIN_STORAGE_2_GROUPS);
                }
                break;
            case 99:
                // Eshop
                $dataSets[] = $this->loadNoteSumForMainStorage($from, $till, SalesDataRepository::ESHOP_GROUPS);
                if ($withOpenSums) {
                    $openSumSets[] = $this->loadOpenSumForMainStorage($from, $till, SalesDataRepository::ESHOP_GROUPS);
                }
                break;
            case 910:
                // Koupelnove vybaveni
                $dataSets[] = $this->loadNoteSumForMainStorage(
                    $from,
                    $till,
                    array_merge(SalesDataRepository::MAIN_STORAGE_1_GROUPS, SalesDataRepository::MAIN_STORAGE_2_GROUPS),
                    [Store::HLUCIN_MAIN_STORAGE]
                );
                if ($withOpenSums) {
                    $openSumSets[] = $this->loadOpenSumForMainStorage(
                        $from,
                        $till,
                        array_merge(SalesDataRepository::MAIN_STORAGE_1_GROUPS, SalesDataRepository::MAIN_STORAGE_2_GROUPS),
                        [Store::HLUCIN_MAIN_STORAGE]
                    );
                }
                break;
            case 4:
                // Ostrava
                $dataSets[] = $this->loadNoteSumForMainStorage($from, $till, SalesDataRepository::OSTRAVA_GROUPS);
                $dataSets[] = $this->loadNoCompanyNoteSumForMainStorage($from, $till);
                if ($withOpenSums) {
                    $openSumSets[] = $this->loadOpenSumForMainStorage($from, $till, SalesDataRepository::OSTRAVA_GROUPS);
                    $openSumSets[] = $this->loadNoCompanyOpenSumForMainStorage($from, $till);
                }
                // Bez breaku, k Ostrave se pridavaji jeste polozky z velkoobchodu
            default:
                if ($storeId > 100) {
                    // Obchodni zastupce
                    $_storeId = $storeId;
                    $storeIdSplit = str_split((string) $storeId);
                    $ozType = (int) array_pop($storeIdSplit);
                    $storeId = array_shift($storeIdSplit);
                    $storeIdSupplement = array_shift($storeIdSplit);
                    if ($storeIdSupplement) {
                        $storeId .= $storeIdSupplement;
                    }
                    $storeId = intval($storeId);
                    if ($ozType < 4){
                        if($ozType == 1 && ($storeId == 2 || $storeId == 8)){
                            $dataSets[] = $this->loadNoteSumForOz2025($storeId, [33], $from, $till, 1);
                            if ($withOpenSums) {
                                $openSumSets[] = $this->loadOpenSumForOz2025($storeId, [33], $from, $till, 1);
                            }
                        }
                        else {
                            $dataSets[] = $this->loadNoteSumForOz($storeId, $ozType, $from, $till);
                            if ($withOpenSums) {
                                $openSumSets[] = $this->loadOpenSumForOz($storeId, $ozType, $from, $till);
                            }
                        }
                        if ($storeId == 4){
                            $dataSets[] = $this->loadNoteSumForOz(9, $ozType, $from, $till);
                            $dataSets[] = $this->loadNoteSumForOz(10, $ozType, $from, $till);
                            if ($withOpenSums) {
                                $openSumSets[] = $this->loadOpenSumForOz(9, $ozType, $from, $till);
                                $openSumSets[] = $this->loadOpenSumForOz(10, $ozType, $from, $till);
                            }
                        }
                        if ($ozType == 3){
                            $dataSets[] = $this->loadNoteSumForMainStorage($from, $till, [intval('9' . $storeId)]);
                            if ($withOpenSums) {
                                $openSumSets[] = $this->loadOpenSumForMainStorage($from, $till, [intval('9' . $storeId)]);
                            }
                        }
                        if ($storeId == 5 && $ozType == 1){
                            $sadsa = 1;
                        }
                    }
                    elseif($ozType == 4 and $storeId == 4){
                        $dataSets[] = $this->loadNoteSumForMainStorage($from, $till, SalesDataRepository::OSTRAVA_GROUPS);
                        $dataSets[] = $this->loadNoCompanyNoteSumForMainStorage($from, $till);
                        $dataSets[] = $this->loadNoteSumForMainStorage($from, $till, array_merge(SalesDataRepository::OSTRAVA_GROUPS, SalesDataRepository::ESHOP_GROUPS, SalesDataRepository::STORE_BUILD_GROUP), [$storeId]);
                        $dataSets[] = $this->loadNoteSumForMainStorage($from, $till, [intval('9' . $storeId)]);
                    }
                    elseif($ozType == 4 and $storeId != 4){
                        $dataSets[] = $this->loadNoteSumForMainStorage($from, $till, SalesDataRepository::ESHOP_GROUPS, [$storeId]);
                        if ($storeId == 3) { //Otrokovice
                            $dataSets[] = $this->loadNoteSumForOz2025(2, [88,77], $from, $till, -1); //Olomouc
                            if ($withOpenSums) {
                                $openSumSets[] = $this->loadOpenSumForOz2025(2, [88,77], $from, $till, -1);
                            }
                        }
                        if ($storeId == 6) { //Teplice
                            $dataSets[] = $this->loadNoteSumForOz2025(8, [88], $from, $till, -1); //Hradec
                            if ($withOpenSums) {
                                $openSumSets[] = $this->loadOpenSumForOz2025(8, [88], $from, $till, -1);
                            }
                        }
                    }
                } else {
                    // Pobocka
                    $dataSets[] = $this->loadNoteSumForStore($storeId, $from, $till);
                    $dataSets[] = $this->loadNoteSumForMainStorage($from, $till, [intval('9' . $storeId)]);

                    if ($storeId == 3){
                        $asda = 1;
                    }

                    if ($withOpenSums) {
                        $openSumSets[] = $this->loadOpenSumForStore($storeId, $from, $till);
                        $openSumSets[] = $this->loadOpenSumForMainStorage($from, $till, [intval('9' . $storeId)]);
                        if ($storeId == 2) { //Olomouc
                            $openSumSets[] = $this->loadOpenSumForOz2025(2, [88,77], $from, $till, -1);//Olomouc
                        }
                        if ($storeId == 8) { //Hradec
                            $openSumSets[] = $this->loadOpenSumForOz2025(8, [88], $from, $till, -1);//Hradec
                        }
                        if ($storeId == 3) { //Otrokovice
                            $openSumSets[] = $this->loadOpenSumForOz2025(2, [88,77], $from, $till, 1);//Olomouc
                        }
                        if ($storeId == 6) { //Teplice
                            $openSumSets[] = $this->loadOpenSumForOz2025(8, [88], $from, $till, 1);//Hradec
                        }
                    }
                    if ($storeId == 2) { //Olomouc
                        $dataSets[] = $this->loadNoteSumForOz2025(2, [88,77], $from, $till, -1);//Olomouc
                    }
                    if ($storeId == 8) { //Hradec
                        $dataSets[] = $this->loadNoteSumForOz2025(8, [88], $from, $till, -1);//Hradec
                    }
                    if ($storeId == 3) { //Otrokovice
                        $dataSets[] = $this->loadNoteSumForOz2025(2, [88,77], $from, $till, 1);//Olomouc
                    }
                    if ($storeId == 6) { //Teplice
                        $dataSets[] = $this->loadNoteSumForOz2025(8, [88], $from, $till, 1);//Hradec
                    }

                }
        }

        foreach ($dataSets as $dataSet) {
            foreach ($dataSet as $row) {
                $result[SalesData::RAW_SALES_DATA] += $row->sells;
                $result[SalesData::RAW_PROFIT_DATA] += $row->profit;
            }
        }

        foreach ($openSumSets as $dataSet) {
            foreach ($dataSet as $row) {
                $result[SalesData::OPEN_SALES_DATA] += $row->sells;
                $result[SalesData::OPEN_PROFIT_DATA] += $row->profit;
            }
        }

        return $result;
    }

    public function loadFilterSalesSum(SalesFilterEntity $filter, array $groups, int $year, int $month): int
    {
        if (!$filter->dealers || !$filter->isValidForData()) {
            return 0;
        }

        $date = \DateTime::createFromFormat('Y-n-d', "$year-$month-01");

        $innerSql = "
            SELECT (
                IF (
                    n.movement_type = %i,
                    ni.discount - (ni.amount * ni.sell_price),
                    (ni.amount * ni.sell_price) - ni.discount
                )
            ) AS sellValue FROM `delivery_note_items` AS ni
            JOIN `delivery_notes` AS n ON ni.note = n.id
            JOIN `stock_variants` AS v ON v.id = ni.item
            JOIN `stock_items` AS i ON i.id = v.item
            JOIN `stock_groups` AS g ON i.group = g.id
            JOIN `delivery_company_depots` AS d ON d.id = n.depot
            JOIN `delivery_company_depots_dealers` AS dd ON dd.depot_id = d.id
            LEFT JOIN `stock_series_items` AS si ON si.item_id = i.id
            WHERE n.movement_type IN %i[]
            AND n.date >= %s
            AND n.date <= %s
            AND g.id IN %i[]
            AND dd.user_id IN %i[]
        ";

        $params = [
            DeliveryNote::TYPE_CANCEL,
            [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
            $date->format(DateTime::DB_DATE),
            $date->modify('last day of this month')->format(DateTime::DB_DATE),
            $groups,
            $filter->dealers
        ];

        if ($filter->company) {
            $innerSql .= ' AND d.company = %i';
            $params[] = $filter->company;
        }

        if ($filter->depot) {
            $innerSql .= ' AND d.voj = %s';
            $params[] = $filter->getDepotVoj();
        }

        if ($filter->series) {
            $innerSql .= ' AND si.series_id = %i';
            $params[] = $filter->series;
        }

        if ($filter->item) {
            $innerSql .= ' AND i.id = %i';
            $params[] = $filter->item;
        }

        $innerSql .= ' GROUP BY ni.id';

        $sql = "
            SELECT SUM(temp.sellValue) FROM ( $innerSql ) AS temp
        ";

        $return =  intval($this->getConnection()->query($sql, ...$params)->fetchField());
        return $return;
    }

    public function loadStoreFilterSalesSumK2(SalesFilterEntity $filter, int $year, int $month, int $producer = null, int $stockGroup = null): int
    {
        $date = \DateTime::createFromFormat('Y-n-d', "$year-$month-01");

        $params = [
            $year,
            $month
        ];

        $innerSql = "
            SELECT sum(k2.value) as sellValue
            FROM `cache_takings_overview_k2` k2
            inner join `delivery_companies` dc on dc.ico = k2.ico
            where k2.type = 3
            AND k2.year = %i
            AND k2.month = %i
        ";

        if ($filter->store != 0) {
            $innerSql .= "
                AND k2.store = %i";
            $params[] = $filter->store;
        }

        if ($filter->stockGroup) {
            $innerSql .= "
                AND k2.group = %i";
            $params[] = $filter->stockGroup->id;
        }

        if ($stockGroup) {
            $innerSql .= "
                AND k2.group = %i";
            $params[] = $stockGroup;
        }

        if ($filter->company) {
            $innerSql .= "
                AND dc.id = %i";
            $params[] = $filter->company;
        }

        if ($filter->producers) {
            $innerSql .= "
                AND k2.producer IN %i[]";
            $params[] = $filter->producers;
        }

        if ($producer) {
            $innerSql .= "
                AND k2.producer = %i";
            $params[] = $producer;
        }

        $return = intval($this->getConnection()->query($innerSql, ...$params)->fetchField());
        return $return;
    }
    public function loadStoreFilterSalesSum(SalesFilterEntity $filter, array $groups, int $year, int $month): int
    {
        if (!$filter->isValidForData()) {
            return 0;
        }

        if ($filter->store) {
            $stores = [$filter->store];
            $storageGroups = [intval('9' . $filter->store)];
        } else {
            $stores = $this->loadStores();
            $storageGroups = [];

            foreach ($stores as $storeId) {
                $storageGroups[] = intval('9' . $storeId);
            }
        }

        $date = \DateTime::createFromFormat('Y-n-d', "$year-$month-01");
        $ostravaHack = '';

        if (!$filter->store || $filter->store === Store::OSTRAVA) {
            $storageGroups = array_merge($storageGroups, SalesDataRepository::OSTRAVA_GROUPS);
            $ostravaHack = '
                OR (
                    n.store IN %i[]
                    AND n.movement_number IN %i[]
                )
            ';
        }

        $innerSql = "
            SELECT (
                IF (
                    n.movement_type = %i,
                    ni.discount - (ni.amount * ni.sell_price),
                    (ni.amount * ni.sell_price) - ni.discount
                )
            ) AS sellValue FROM `delivery_note_items` AS ni
            JOIN `delivery_notes` AS n ON ni.note = n.id
            JOIN `stock_variants` AS v ON v.id = ni.item
            JOIN `stock_items` AS i ON i.id = v.item
            JOIN `stock_groups` AS g ON i.group = g.id
            LEFT JOIN `stock_series_items` AS si ON si.item_id = i.id
            LEFT JOIN `delivery_company_depots` AS d ON d.id = n.depot
            LEFT JOIN `delivery_company_groups` AS cg ON cg.id = d.group
            WHERE n.movement_type IN %i[]
            AND n.date >= %s
            AND n.date <= %s
            AND g.id IN %i[]
        ";

        $params = [
            DeliveryNote::TYPE_CANCEL,
            [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
            $date->format(DateTime::DB_DATE),
            $date->modify('last day of this month')->format(DateTime::DB_DATE),
            $groups
        ];

        if ($filter->oz || $filter->store === Store::OSTRAVA_MAIN_STORAGE) {
            $innerSql .= "
                AND n.store = %i
                AND cg.number IN %i[]
            ";
            $params[] = $filter->store;
            $params[] = $filter->oz === 1 ? SalesDataRepository::STORE_OZ_1_GROUP : SalesDataRepository::STORE_OZ_2_GROUP;
        } else {
            $innerSql .= "
                AND (
                    n.store IN %i[]
                    OR (
                        n.store IN %i[]
                        AND cg.number IN %i[]
                    )
                    $ostravaHack
                )
            ";
            $params[] = $stores;
            $params[] = Store::MAIN_STORAGES;
            $params[] = $storageGroups;

            if ($ostravaHack) {
                $params[] = Store::MAIN_STORAGES;
                $params[] = SalesDataRepository::NO_COMPANY_MOVEMENT_NUMBERS;
            }
        }

        if ($filter->company > 0) {
            $innerSql .= ' AND d.company = %i';
            $params[] = $filter->company;
        }

        if ($filter->company === StoreOverviewFilter::END_CUSTOMER_ID) {
            $innerSql .= '
                AND (
                    n.movement_number IN %i[]
                    OR (
                        n.movement_number IN %i[]
                        AND cg.number IN %i[]
                    )
                )
            ';

            $params[] = SalesDataRepository::NO_COMPANY_MOVEMENT_NUMBERS;
            $params[] = SalesDataRepository::COMPANY_MOVEMENT_NUMBERS;
            $params[] = SalesDataRepository::END_USER_GROUPS;
        }

        if ($filter->company === StoreOverviewFilter::COMPANY_CUSTOMER_ID) {
            $innerSql .= '
                AND n.movement_number IN %i[]
                AND cg.number NOT IN %i[]
            ';

            $params[] = SalesDataRepository::COMPANY_MOVEMENT_NUMBERS;
            $params[] = SalesDataRepository::END_USER_GROUPS;
        }

        if ($filter->series) {
            $innerSql .= ' AND si.series_id = %i';
            $params[] = $filter->series;
        }

        if ($filter->item) {
            $innerSql .= ' AND i.id = %i';
            $params[] = $filter->item;
        }

        $innerSql .= ' GROUP BY ni.id';

        $sql = "
            SELECT SUM(temp.sellValue) FROM ( $innerSql ) AS temp
        ";

        $return = intval($this->getConnection()->query($sql, ...$params)->fetchField());

        return $return;
    }

    public function loadOutletData(int $year, int $month, int $outletType): array
    {
        $date = (new DateTime())->setDate($year, $month, 1);
        $dateFrom = $date->format(DateTime::DB_DATE);
        $dateTo = $date->modify('+1 month')->format(DateTime::DB_DATE);

        $sql = '
            SELECT SUM(
                IF (
                    n.movement_type = %i,
                    ni.discount - (ni.amount * ni.sell_price),
                    (ni.amount * ni.sell_price) - ni.discount
                )
            ) AS sell
            FROM `delivery_note_items` AS ni
            JOIN `delivery_notes` AS n ON n.id = ni.note
            WHERE n.movement_type IN %i[]
            AND n.store IN %i[]
            AND ni.outlet_type = %i
            AND n.date >= %s
            AND n.date < %s
        ';

        $params = [
            DeliveryNote::TYPE_CANCEL,
            [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
            Store::MAIN_STORAGES,
            $outletType,
            $dateFrom,
            $dateTo
        ];

        $mainStorageSales = $this->getConnection()->query($sql, ...$params)->fetchField();

        $sql = '
            SELECT d.voj, SUM((ni.amount * ni.sell_price) - ni.discount) AS sell
            FROM `delivery_note_items` AS ni
            JOIN `delivery_notes` AS n ON n.id = ni.note
            JOIN `delivery_company_depots` AS d ON n.depot = d.id
            WHERE n.movement_type = %i
            AND n.store IN %i[]
            AND ni.outlet_type = %i
            AND n.date >= %s
            AND n.date < %s
            GROUP BY d.voj
        ';

        $params = [
            DeliveryNote::TYPE_TRANSFER_OUT,
            Store::MAIN_STORAGES,
            $outletType,
            $dateFrom,
            $dateTo
        ];

        $outletSales = $this->getConnection()->query($sql, ...$params)->fetchPairs('voj', 'sell');
        $outletSales[Store::MAIN_STORAGE] = $mainStorageSales;
        return $outletSales;
    }

    private function loadNoteSumForMainStorage(string $fromDate, string $toDate, array $groups, array $storages = Store::MAIN_STORAGES): array
    {
        $sql = '
            SELECT SUM(IF (temp.movement_type = %i, -temp.sellSum, temp.sellSum)) AS sells,
                SUM(IF (
                    temp.movement_type = %i,
                    -(temp.taxFreeSum - IFNULL(temp.buySum, 0)),
                    temp.taxFreeSum - IFNULL(temp.buySum, 0)
                )) AS profit
            FROM (
                SELECT n.id, n.movement_type, n.net_sum AS sellSum, (n.net_sum - n.tax_sum) AS taxFreeSum, SUM(ni.amount * ni.buy_price) AS buySum
                FROM `delivery_notes` AS n
                LEFT JOIN `delivery_note_items` AS ni ON ni.note = n.id
                JOIN `delivery_company_depots` AS d ON d.id = n.depot
                JOIN `delivery_company_groups` AS g ON g.id = d.group
                JOIN `sys_stores` AS s ON s.id = n.store
                WHERE n.movement_type IN %i[]
                AND n.date >= %s
                AND n.date <= %s
                AND g.number IN %i[]
                AND s.id IN %i[]
                GROUP BY n.id
            ) AS temp
        ';

        $return = $this->getConnection()->query(
                $sql,
                DeliveryNote::TYPE_CANCEL,
                DeliveryNote::TYPE_CANCEL,
                [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
                $fromDate,
                $toDate,
                $groups,
                $storages
            )->fetchAll();
        return $return;
    }

    private function loadOpenSumForMainStorage(string $fromDate, string $toDate, array $groups, array $storages = Store::MAIN_STORAGES): array
    {
        $sql = '
            SELECT SUM(temp.sellSum) AS sells, SUM(temp.taxFreeSum - IFNULL(temp.buySum, 0)) AS profit
            FROM (
                SELECT n.id, n.net_sum AS sellSum, (n.net_sum - n.tax_sum) AS taxFreeSum, SUM(ni.amount * ni.buy_price) AS buySum
                FROM `delivery_notes` AS n
                LEFT JOIN `delivery_note_items` AS ni ON ni.note = n.id
                JOIN `delivery_company_depots` AS d ON d.id = n.depot
                JOIN `delivery_company_groups` AS g ON g.id = d.group
                JOIN `sys_stores` AS s ON s.id = n.store
                WHERE n.movement_type = %i
                AND n.state_char != "X"
                AND n.date >= %s
                AND n.date <= %s
                AND g.number IN %i[]
                AND s.id IN %i[]
                GROUP BY n.id
            ) AS temp
        ';

        return $this->getConnection()->query(
            $sql,
            DeliveryNote::TYPE_SALE,
            $fromDate,
            $toDate,
            $groups,
            $storages
        )->fetchAll();
    }

    private function loadNoteSumForStore(int $storeId, string $fromDate, string $toDate): array
    {
        $sql = '
            SELECT SUM(IF (temp.movement_type = %i, -temp.sellSum, temp.sellSum)) AS sells,
                SUM(IF (
                    temp.movement_type = %i,
                    -(temp.taxFreeSum - IFNULL(temp.buySum, 0)),
                    temp.taxFreeSum - IFNULL(temp.buySum, 0)
                )) AS profit
            FROM (
                SELECT n.id, n.movement_type, n.net_sum AS sellSum, (n.net_sum - n.tax_sum) AS taxFreeSum, SUM(ni.amount * ni.buy_price) AS buySum
                FROM `delivery_notes` AS n
                LEFT JOIN `delivery_note_items` AS ni ON ni.note = n.id
                JOIN `sys_stores` AS s ON s.id = n.store
                WHERE n.movement_type IN %i[]
                AND n.date >= %s
                AND n.date <= %s
                AND s.id = %i
                GROUP BY n.id
            ) AS temp
        ';

        $return = $this->getConnection()->query(
            $sql,
            DeliveryNote::TYPE_CANCEL,
            DeliveryNote::TYPE_CANCEL,
            [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
            $fromDate,
            $toDate,
            $storeId
        )->fetchAll();

        return $return;
    }

    private function loadOpenSumForStore(int $storeId, string $fromDate, string $toDate): array
    {
        $sql = '
            SELECT SUM(temp.sellSum) AS sells, SUM(temp.taxFreeSum - IFNULL(temp.buySum, 0)) AS profit
            FROM (
                SELECT n.id, n.net_sum AS sellSum, (n.net_sum - n.tax_sum) AS taxFreeSum, SUM(ni.amount * ni.buy_price) AS buySum
                FROM `delivery_notes` AS n
                LEFT JOIN `delivery_note_items` AS ni ON ni.note = n.id
                JOIN `sys_stores` AS s ON s.id = n.store
                WHERE n.movement_type = %i
                AND n.state_char != "X"
                AND n.date >= %s
                AND n.date <= %s
                AND s.id = %i
                GROUP BY n.id
            ) AS temp
        ';

        return $this->getConnection()->query(
            $sql,
            DeliveryNote::TYPE_SALE,
            $fromDate,
            $toDate,
            $storeId
        )->fetchAll();
    }

    private function loadNoCompanyNoteSumForMainStorage(string $fromDate, string $toDate): array
    {
        $sql = '
            SELECT SUM(IF (temp.movement_type = %i, -temp.sellSum, temp.sellSum)) AS sells,
                SUM(IF (
                    temp.movement_type = %i,
                    -(temp.taxFreeSum - IFNULL(temp.buySum, 0)),
                    temp.taxFreeSum - IFNULL(temp.buySum, 0)
                )) AS profit
            FROM (
                SELECT n.id, n.movement_type, n.net_sum AS sellSum, (n.net_sum - n.tax_sum) AS taxFreeSum, SUM(ni.amount * ni.buy_price) AS buySum
                FROM `delivery_notes` AS n
                LEFT JOIN `delivery_note_items` AS ni ON ni.note = n.id
                JOIN `sys_stores` AS s ON s.id = n.store
                WHERE n.movement_number IN %i[]
                AND n.date >= %s
                AND n.date <= %s
                AND s.id IN %i[]
                GROUP BY n.id
            ) AS temp
        ';

        return $this->getConnection()->query(
            $sql,
            DeliveryNote::TYPE_CANCEL,
            DeliveryNote::TYPE_CANCEL,
            SalesDataRepository::NO_COMPANY_MOVEMENT_NUMBERS,
            $fromDate,
            $toDate,
            Store::MAIN_STORAGES
        )->fetchAll();
    }

    private function loadNoCompanyOpenSumForMainStorage(string $fromDate, string $toDate): array
    {
        $sql = '
            SELECT SUM(temp.sellSum) AS sells, SUM(temp.taxFreeSum - IFNULL(temp.buySum, 0)) AS profit
            FROM (
                SELECT n.id, n.net_sum AS sellSum, (n.net_sum - n.tax_sum) AS taxFreeSum, SUM(ni.amount * ni.buy_price) AS buySum
                FROM `delivery_notes` AS n
                LEFT JOIN `delivery_note_items` AS ni ON ni.note = n.id
                JOIN `sys_stores` AS s ON s.id = n.store
                WHERE n.movement_number IN %i[]
                AND n.state_char != "X"
                AND n.date >= %s
                AND n.date <= %s
                AND s.id IN %i[]
                GROUP BY n.id
            ) AS temp
        ';

        return $this->getConnection()->query(
            $sql,
            [504, 506],
            $fromDate,
            $toDate,
            Store::MAIN_STORAGES
        )->fetchAll();
    }

    private function loadNoteSumForOz(int $storeId, int $ozType, string $fromDate, string $toDate): array
    {
        switch ($ozType){
            case 1:
                $groups = SalesDataRepository::STORE_OZ_1_GROUP;
                break;
            case 2:
                $groups = SalesDataRepository::STORE_OZ_2_GROUP;
                break;
            case 3:
                $groups = SalesDataRepository::STORE_BUILD_GROUP;
                break;
        }
        $sql = '
            SELECT SUM(IF (temp.movement_type = %i, -temp.sellSum, temp.sellSum)) AS sells,
                SUM(IF (
                    temp.movement_type = %i,
                    -(temp.taxFreeSum - IFNULL(temp.buySum, 0)),
                    temp.taxFreeSum - IFNULL(temp.buySum, 0)
                )) AS profit
            FROM (
                SELECT n.id, n.movement_type, n.net_sum AS sellSum, (n.net_sum - n.tax_sum) AS taxFreeSum, SUM(ni.amount * ni.buy_price) AS buySum
                FROM `delivery_notes` AS n
                LEFT JOIN `delivery_note_items` AS ni ON ni.note = n.id
                JOIN `delivery_company_depots` AS d ON d.id = n.depot
                JOIN `delivery_company_groups` AS g ON g.id = d.group
                JOIN `sys_stores` AS s ON s.id = n.store
                WHERE n.movement_type IN %i[]
                AND n.date >= %s
                AND n.date <= %s
                AND g.number IN %i[]
                AND s.id = %i
                GROUP BY n.id
            ) AS temp
        ';

        return $this->getConnection()->query(
            $sql,
            DeliveryNote::TYPE_CANCEL,
            DeliveryNote::TYPE_CANCEL,
            [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
            $fromDate,
            $toDate,
            $groups,
            $storeId
        )->fetchAll();
    }

    private function loadNoteSumForOz2025(int $storeId, array $groups = [], string $fromDate, string $toDate, int $multiply): array
    {
        $datum = new DateTime($fromDate);
        $rok = $datum->format("Y");

        if ($rok < 2025){
            return $this->getConnection()->query('SELECT 0 as sells, 0 as profit')->fetchAll();
        }

        $sql = '
            SELECT SUM(IF (temp.movement_type = %i, -temp.sellSum, temp.sellSum)) * %i AS sells,
                SUM(IF (
                    temp.movement_type = %i,
                    -(temp.taxFreeSum - IFNULL(temp.buySum, 0)),
                    temp.taxFreeSum - IFNULL(temp.buySum, 0)
                )) * %i AS profit
            FROM (
                SELECT n.id, n.movement_type, n.net_sum AS sellSum, (n.net_sum - n.tax_sum) AS taxFreeSum, SUM(ni.amount * ni.buy_price) AS buySum
                FROM `delivery_notes` AS n
                LEFT JOIN `delivery_note_items` AS ni ON ni.note = n.id
                JOIN `delivery_company_depots` AS d ON d.id = n.depot
                JOIN `delivery_company_groups` AS g ON g.id = d.group
                JOIN `sys_stores` AS s ON s.id = n.store
                WHERE n.movement_type IN %i[]
                AND n.date >= %s
                AND n.date <= %s
                AND g.number IN %i[]
                AND s.id = %i
                GROUP BY n.id
            ) AS temp
        ';

        return $this->getConnection()->query(
            $sql,
            DeliveryNote::TYPE_CANCEL,
            $multiply,
            DeliveryNote::TYPE_CANCEL,
            $multiply,
            [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
            $fromDate,
            $toDate,
            $groups,
            $storeId
        )->fetchAll();
    }

    private function loadOpenSumForOz2025(int $storeId, array $groups = [], string $fromDate, string $toDate, int $multiply): array
    {
        $datum = new DateTime($fromDate);
        $rok = $datum->format("Y");

        if ($rok < 2025){
            return $this->getConnection()->query('SELECT 0 as sells, 0 as profit')->fetchAll();
        }

        $sql = '
            SELECT SUM(temp.sellSum) * %i AS sells, SUM(temp.taxFreeSum - IFNULL(temp.buySum, 0)) * %i AS profit
            FROM (
                SELECT n.id, n.net_sum AS sellSum, (n.net_sum - n.tax_sum) AS taxFreeSum, SUM(ni.amount * ni.buy_price) AS buySum
                FROM `delivery_notes` AS n
                LEFT JOIN `delivery_note_items` AS ni ON ni.note = n.id
                JOIN `delivery_company_depots` AS d ON d.id = n.depot
                JOIN `delivery_company_groups` AS g ON g.id = d.group
                JOIN `sys_stores` AS s ON s.id = n.store
                WHERE n.movement_type = %i
                AND n.state_char != "X"
                AND n.date >= %s
                AND n.date <= %s
                AND g.number IN %i[]
                AND s.id = %i
                GROUP BY n.id
            ) AS temp
        ';

        return $this->getConnection()->query(
            $sql,
            $multiply,
            $multiply,
            DeliveryNote::TYPE_SALE,
            $fromDate,
            $toDate,
            $groups,
            $storeId
        )->fetchAll();
    }

    private function loadOpenSumForOz(int $storeId, int $ozType, string $fromDate, string $toDate): array
    {
        switch ($ozType) {
            case 1:
                $groups = SalesDataRepository::STORE_OZ_1_GROUP;
                break;
            case 2:
                $groups = SalesDataRepository::STORE_OZ_2_GROUP;
                break;
            case 3:
                $groups = SalesDataRepository::STORE_BUILD_GROUP;
                break;
        }

        //$groups = $ozType === 1 ? SalesDataRepository::STORE_OZ_1_GROUP : SalesDataRepository::STORE_OZ_2_GROUP;
        $sql = '
            SELECT SUM(temp.sellSum) AS sells, SUM(temp.taxFreeSum - IFNULL(temp.buySum, 0)) AS profit
            FROM (
                SELECT n.id, n.net_sum AS sellSum, (n.net_sum - n.tax_sum) AS taxFreeSum, SUM(ni.amount * ni.buy_price) AS buySum
                FROM `delivery_notes` AS n
                LEFT JOIN `delivery_note_items` AS ni ON ni.note = n.id
                JOIN `delivery_company_depots` AS d ON d.id = n.depot
                JOIN `delivery_company_groups` AS g ON g.id = d.group
                JOIN `sys_stores` AS s ON s.id = n.store
                WHERE n.movement_type = %i
                AND n.state_char != "X"
                AND n.date >= %s
                AND n.date <= %s
                AND g.number IN %i[]
                AND s.id = %i
                GROUP BY n.id
            ) AS temp
        ';

        return $this->getConnection()->query(
            $sql,
            DeliveryNote::TYPE_SALE,
            $fromDate,
            $toDate,
            $groups,
            $storeId
        )->fetchAll();
    }

    private function loadTotalNoteSumForMainStorages(string $fromDate, string $toDate): array
    {
        // Skupiny z velkoobchodnich MOP, ktere nepatri velkoobchodu
        $groups = array_merge(
            SalesDataRepository::OSTRAVA_GROUPS,
            SalesDataRepository::ESHOP_GROUPS,
            range(91, 98)
        );

        $sql = '
            SELECT SUM(IF (temp.movement_type = %i, -temp.sellSum, temp.sellSum)) AS sells,
                SUM(IF (
                    temp.movement_type = %i,
                    -(temp.taxFreeSum - IFNULL(temp.buySum, 0)),
                    temp.taxFreeSum - IFNULL(temp.buySum, 0)
                )) AS profit
            FROM (
                SELECT n.id, n.movement_type, n.net_sum AS sellSum, (n.net_sum - n.tax_sum) AS taxFreeSum, SUM(ni.amount * ni.buy_price) AS buySum
                FROM `delivery_notes` AS n
                LEFT JOIN `delivery_note_items` AS ni ON ni.note = n.id
                JOIN `delivery_company_depots` AS d ON d.id = n.depot
                JOIN `delivery_company_groups` AS g ON g.id = d.group
                JOIN `sys_stores` AS s ON s.id = n.store
                WHERE n.movement_type IN %i[]
                AND n.movement_number NOT IN %i[]
                AND n.date >= %s
                AND n.date <= %s
                AND g.number NOT IN %i[]
                AND s.id IN %i[]
                GROUP BY n.id
            ) AS temp
        ';

        return $this->getConnection()->query(
            $sql,
            DeliveryNote::TYPE_CANCEL,
            DeliveryNote::TYPE_CANCEL,
            [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
            SalesDataRepository::NO_COMPANY_MOVEMENT_NUMBERS,
            $fromDate,
            $toDate,
            $groups,
            Store::MAIN_STORAGES
        )->fetchAll();
    }

    private function loadStores(): array
    {
        return $this->getRepository()->getModel()->getRepository(StoreRepository::class)
            ->loadSimpleStoreIds();
    }

    public function loadK2real(int $store, \DateTimeInterface $date): array
    {
        $toDate = $date->format('Y-m-d');
        $sql = '
            select
                sum(ap.real_sale) as real_sale,
                sum(ap.real_profit) as real_profit,
                sum(ap.sales_sale) as sales_sale,
                sum(ap.sales_profit) as sales_profit,
                sum(ap.release_sale) as release_sale,
                sum(ap.release_profit) as release_profit
            from (
				select 
					store as store, 
					ifnull(real_sale, 0) as real_sale,
					ifnull(real_profit, 0) as real_profit,
					sales_sale as sales_sale,
					sales_profit as sales_profit,
					0 as release_sale,
					0 as release_profit
				from delivery_notes_k2
				where date <= %s
					and month(date) = month(%s)
					and year(date) = year(%s)
					and store = %i
				union all
				select 
					store as store, 
					0 as real_sale,
					0 as real_profit,
					sales_sale as sales_sale,
					sales_profit as sales_profit,
					0 as release_sale,
					0 as release_profit
				from delivery_notes_k2
				where date < MAKEDATE(year(date), month(%s))
					and real_sale is null
					and store = %i
			) as ap
		group by ap.store        
		';

        return $this->getConnection()->query(
            $sql,
            $toDate,
            $toDate,
            $toDate,
            $store,
            $toDate,
            $store
        )->fetchAll();
    }

    public function updateDifferences(int $year): void
    {
        $this->getConnection()->query('CALL sp_update_sales_data('. $year . ')');
    }


}
