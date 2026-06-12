<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Companies;

use App\Core\Orm\BaseConventions;
use App\Core\Orm\BaseMapper;
use App\Core\Utils\DateTime;
use App\Modules\DeliveryModule\Component\Entity\SalesFilterEntity;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\DeliveryModule\Orm\SalesData\SalesDataRepository;
use App\Modules\DeliveryModule\Presenters\DealerPresenter;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Modules\SystemModule\Orm\Stores\StoreRepository;
use Nette\Utils\Paginator;

class CompanyMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_companies';

    /** JSON column definition */
    protected function createConventions() : BaseConventions
    {
        $conventions = parent::createConventions();
        $conventions->addMapping(
            'information',
            'information',
            static fn($val) =>  json_decode($val ?? '[]', true),
            static fn($val) =>  json_encode($val ?? [])
        );

        return $conventions;
    }

    public function loadDealerOverviewGridData(SalesFilterEntity $filter, ?Paginator $paginator, int $gridType): array
    {
        if (!$filter->years || !$filter->dealers) {
            return [];
        }

        $stockGroups = $filter->getStockGroups();
        $orderYear = $filter->years[0];
        $dateFrom = (new \DateTimeImmutable())->setDate($orderYear, $filter->month ?: 1, 1);
        $dateTill = $dateFrom->setDate($orderYear, $filter->month ?: 12, 1)->modify('last day of this month');
        //$dateFrom = (new \DateTimeImmutable())->setDate($orderYear, 1, 1);
        //$dateTill = (new \DateTimeImmutable())->setDate($orderYear, 7, 31);
        $companyColumn = ['c.id'];
        $tempCompanyColumn = ['tc.id'];
        $ttCompanyColumn = ['temp.id'];

        if ($gridType === DealerPresenter::DEPOT_GRID) {
            $companyColumn[] = 'd.voj';
            $tempCompanyColumn[] = 'td.voj';
            $ttCompanyColumn[] = 'temp.voj';
        }

        $params = [
            $tempCompanyColumn,
            $ttCompanyColumn,
            $companyColumn,
            DeliveryNote::TYPE_CANCEL,
            [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
            $dateFrom->format(DateTime::DB_DATE),
            $dateTill->format(DateTime::DB_DATE),
            [0, Store::INTERNAL_ICO],
            $stockGroups ?: [0],
            $filter->dealers
        ];

        $innerSql = '
            SELECT %column[],
                IF (
                    n.movement_type = %i,
                    ni.discount - (ni.amount * ni.sell_price),
                    (ni.amount * ni.sell_price) - ni.discount
                ) AS sellValue
            FROM `delivery_note_items` AS ni
            JOIN `delivery_notes` AS n ON ni.note = n.id
            JOIN `stock_variants` AS v ON v.id = ni.item
            JOIN `stock_items` AS i ON i.id = v.item
            JOIN `stock_groups` AS g ON i.group = g.id
            JOIN `delivery_company_depots` AS d ON d.id = n.depot
            JOIN `delivery_companies` AS c ON c.id = d.company
            LEFT JOIN `delivery_company_depots_dealers` AS dd ON dd.depot_id = d.id
            LEFT JOIN `delivery_company_groups` AS cg ON cg.id = d.group
            LEFT JOIN `stock_series_items` AS si ON si.item_id = i.id
            WHERE n.movement_type IN %i[]
            AND n.date >= %s
            AND n.date <= %s
            AND c.ico NOT IN %i[]
            AND g.id IN %i[]
            AND cg.number > 0
            AND dd.user_id IN %i[]
        ';

        if ($filter->company) {
            $innerSql .= ' AND c.id = %i';
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
        $joinCondition = 'tt.id = tc.id';
        $groupBy = ['temp.id'];

        if ($gridType === DealerPresenter::DEPOT_GRID) {
            $joinCondition .= ' AND tt.voj = td.voj';
            $groupBy[] = 'temp.voj';
        }

        $sql = "
            SELECT %column[],
                CONCAT(LPAD(tc.ico, 8, '0'), ' - ', tc.name, ' (', td.title , ')') AS depotName,
                CONCAT(LPAD(tc.ico, 8, '0'), ' - ', tc.name) AS companyName,
                tt.sumValue
            FROM `delivery_companies` AS tc
            LEFT JOIN `delivery_company_depots` AS td ON tc.id = td.company
            LEFT JOIN `delivery_company_groups` AS tcg ON tcg.id = td.group
            LEFT JOIN `delivery_company_depots_dealers` AS tdd ON tdd.depot_id = td.id
            LEFT JOIN (
                SELECT %column[], SUM(temp.sellValue) AS sumValue FROM ( $innerSql ) AS temp
                GROUP BY %column[]
            ) AS tt ON $joinCondition
            WHERE tc.ico NOT IN %i[]
            AND tcg.number > 0
            AND tdd.user_id IN %i[]
        ";

        $params[] = $groupBy;
        $params[] = [0, Store::INTERNAL_ICO];
        $params[] = $filter->dealers;

        if ($filter->company) {
            $sql .= ' AND tc.id = %i';
            $params[] = $filter->company;
        }

        if ($filter->depot) {
            $sql .= ' AND td.voj = %s';
            $params[] = $filter->getDepotVoj();
        }

        $sql .= ' GROUP BY %column[] ORDER BY sumValue DESC';
        $params[] = $tempCompanyColumn;

        if (!$paginator) {
            return $this->getConnection()->query($sql, ...$params)->fetchPairs(
                $gridType === DealerPresenter::DEPOT_GRID ? 'depotName' :'companyName',
                'sumValue'
            );
        }

        $sql .= ' LIMIT %i OFFSET %i';
        $params[] = $paginator->getItemsPerPage();
        $params[] = $paginator->getOffset();

        $result = $this->getConnection()->query($sql, ...$params)->fetchAll();

        foreach ($result as $row) {
            $voj = $gridType === DealerPresenter::DEPOT_GRID ? $row->voj : null;

            foreach ($filter->years as $year) {
                $row->$year = $year === $orderYear
                    ? (float) $row->sumValue
                    : $this->loadCompanyOverviewData($filter, $row->id, $year, $stockGroups, $gridType, $voj);
            }
        }

        return $result;
    }

    public function loadStoreOverviewGridData(SalesFilterEntity $filter, Paginator $paginator = null): array
    {
        if (!$filter->years) {
            return [];
        }

        $stockGroups = $filter->getStockGroups();
        $orderYear = $filter->years[0];
        $dateFrom = (new \DateTimeImmutable())->setDate($orderYear, $filter->month ?: 1, 1);
        $dateTill = $dateFrom->setDate($orderYear, $filter->month ?: 12, 1)->modify('last day of this month');

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

        $params = [
            DeliveryNote::TYPE_CANCEL,
            [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
            $dateFrom->format(DateTime::DB_DATE),
            $dateTill->format(DateTime::DB_DATE),
            [0, Store::INTERNAL_ICO],
            $stockGroups ?: [0]
        ];

        $innerSql = "
            SELECT c.id,
                IF (
                    n.movement_type = %i,
                    ni.discount - (ni.amount * ni.sell_price),
                    (ni.amount * ni.sell_price) - ni.discount
                ) AS sellValue
            FROM `delivery_note_items` AS ni
            JOIN `delivery_notes` AS n ON ni.note = n.id
            JOIN `stock_variants` AS v ON v.id = ni.item
            JOIN `stock_items` AS i ON i.id = v.item
            JOIN `stock_groups` AS g ON i.group = g.id
            JOIN `delivery_company_depots` AS d ON d.id = n.depot
            JOIN `delivery_companies` AS c ON c.id = d.company
            LEFT JOIN `delivery_company_groups` AS cg ON cg.id = d.group
            LEFT JOIN `stock_series_items` AS si ON si.item_id = i.id
            WHERE n.movement_type IN %i[]
            AND n.date >= %s
            AND n.date <= %s
            AND c.ico NOT IN %i[]
            AND g.id IN %i[]
            AND cg.number > 0
        ";

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

        if ($filter->company) {
            $innerSql .= ' AND c.id = %i';
            $params[] = $filter->company;
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
            SELECT tc.id,
                CONCAT(LPAD(tc.ico, 8, '0'), ' - ', tc.name) AS companyName,
                tt.sumValue
            FROM `delivery_companies` AS tc
            LEFT JOIN `delivery_company_depots` AS td ON tc.id = td.company
            LEFT JOIN `delivery_company_groups` AS tcg ON tcg.id = td.group
            LEFT JOIN (
                SELECT temp.id, SUM(temp.sellValue) AS sumValue FROM ( $innerSql ) AS temp
                GROUP BY temp.id
            ) AS tt ON tt.id = tc.id
            WHERE tc.ico NOT IN %i[]
            AND tcg.number > 0
        ";

        $params[] = [0, Store::INTERNAL_ICO];

        if ($filter->oz || $filter->store === Store::OSTRAVA_MAIN_STORAGE) {
            $sql .= '
                AND td.store = %i
                AND tcg.number IN %i[]
            ';
            $params[] = $filter->store;
            $params[] = $filter->oz === 1 ? SalesDataRepository::STORE_OZ_1_GROUP : SalesDataRepository::STORE_OZ_2_GROUP;
        } else {
            $sql .= 'AND td.store IN %i[]';
            $params[] = $stores;
        }

        if ($filter->company) {
            $sql .= ' AND tc.id = %i';
            $params[] = $filter->company;
        }

        $sql .= ' GROUP BY tc.id ORDER BY sumValue DESC';

        if (!$paginator) {
            return $this->getConnection()->query($sql, ...$params)->fetchPairs('companyName', 'sumValue');
        }

        $sql .= ' LIMIT %i OFFSET %i';
        $params[] = $paginator->getItemsPerPage();
        $params[] = $paginator->getOffset();

        $result = $this->getConnection()->query($sql, ...$params)->fetchAll();

        foreach ($result as $row) {
            foreach ($filter->years as $year) {
                $row->$year = $year === $orderYear
                    ? (float) $row->sumValue
                    : $this->loadCompanyStoreOverviewData($filter, $row->id, $year, $stockGroups);
            }
        }

        return $result;
    }

    private function loadCompanyOverviewData(SalesFilterEntity $filter, int $company, int $year, array $groups, int $gridType, string $voj = null): float
    {
        $innerSql = '
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
            LEFT JOIN `delivery_company_depots_dealers` AS dd ON dd.depot_id = d.id
            LEFT JOIN `stock_series_items` AS si ON si.item_id = i.id
            WHERE n.movement_type IN %i[]
            AND n.date >= %s
            AND n.date <= %s
            AND g.id IN %i[]
            AND d.company = %i
            AND dd.user_id IN %i[]
        ';

        $dateFrom = (new \DateTimeImmutable())->setDate($year, $filter->month ?: 1, 1);
        $dateTill = $dateFrom->setDate($year, $filter->month ?: 12, 1)->modify('last day of this month');
//        $dateFrom = (new \DateTimeImmutable())->setDate($year, 1, 1);
//        $dateTill = $dateFrom->setDate($year, 7, 31)->modify('last day of this month');
        $params = [
            DeliveryNote::TYPE_CANCEL,
            [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
            $dateFrom->format(DateTime::DB_DATE),
            $dateTill->format(DateTime::DB_DATE),
            $groups ?: [0],
            $company,
            $filter->dealers
        ];

        if ($gridType === DealerPresenter::DEPOT_GRID) {
            $innerSql .= ' AND d.voj = %s';
            $params[] = $voj;
        }

        if ($gridType === DealerPresenter::COMPANY_GRID && $filter->depot) {
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

        return $this->getConnection()->query($sql, ...$params)->fetchField() ?? 0;
    }

    private function loadCompanyStoreOverviewData(SalesFilterEntity $filter, int $company, int $year, array $groups): float
    {
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
            JOIN `delivery_company_depots` AS d ON d.id = n.depot
            LEFT JOIN `delivery_company_groups` AS cg ON cg.id = d.group
            LEFT JOIN `stock_series_items` AS si ON si.item_id = i.id
            WHERE n.movement_type IN %i[]
            AND n.date >= %s
            AND n.date <= %s
            AND g.id IN %i[]
            AND d.company = %i
        ";

        $dateFrom = (new \DateTimeImmutable())->setDate($year, $filter->month ?: 1, 1);
        $dateTill = $dateFrom->setDate($year, $filter->month ?: 12, 1)->modify('last day of this month');
        $params = [
            DeliveryNote::TYPE_CANCEL,
            [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
            $dateFrom->format(DateTime::DB_DATE),
            $dateTill->format(DateTime::DB_DATE),
            $groups ?: [0],
            $company
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

        return $this->getConnection()->query($sql, ...$params)->fetchField() ?? 0;
    }

    private function loadStores(): array
    {
        return $this->getRepository()->getModel()->getRepository(StoreRepository::class)
            ->loadSimpleStoreIds();
    }
}
