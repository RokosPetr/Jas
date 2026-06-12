<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\MainStorageOrders;

use App\Core\Orm\BaseMapper;
use App\Modules\StockModule\Orm\StockItems\StockItem;
use App\Modules\StockModule\Orm\StockSeries\StockSeries;
use Nextras\Dbal\QueryBuilder\QueryBuilder;

class MainStorageOrderMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_main_storage_orders';

    public function findByLikeForDatagrid(array $filter, array $order = []): QueryBuilder
    {
        if (isset($filter['state']) && $filter['state'] == MainStorageOrder::STATE_NOT_STOCKED) {
            $filter['state'] = [MainStorageOrder::STATE_NEW, MainStorageOrder::STATE_PARTLY_STOCKED];
        }
        return parent::findByLikeForDatagrid($filter, $order);
    }

    public function loadProducersForFilter(): array
    {
        $producers = [];
        $globals = [];
        $globalSeries = array_flip(StockSeries::GLOBAL_SERIES);
        $sql = '
            SELECT p.id, p.name, i.global_producer, IF (p.number IN %i[], 1, 0) AS tiles
            FROM `stock_items` AS i
            JOIN `stock_producers` AS p ON p.id = i.producer
            WHERE i.status = %i
            GROUP BY id, global_producer
            ORDER BY tiles DESC, name
        ';

        $result = $this->getConnection()->query(
            $sql,
            StockItem::OSTRAVA_MAIN_STORAGE_PRODUCERS,
            StockItem::STATUS_PALETTE
        )->fetchAll();

        foreach ($result as $row) {
            if (!isset($producers[$row->id])) {
                $producers[$row->id] = $row->name;
            }
            $globalKey = $row->global_producer ? $globalSeries[$row->global_producer] : null;
            if ($globalKey && !isset($globals[$globalKey])) {
                $globals[$globalKey] = StockSeries::GLOBAL_SERIES_LABELS[$globalKey];
            }
        }

        return $producers + $globals;
    }

    public function loadSeriesForFilter(): array
    {
        $sql = '
            SELECT s.id, s.name
            FROM `stock_items` AS i
            JOIN `stock_series_items` AS si ON si.item_id = i.id
            JOIN `stock_series` AS s ON s.id = si.series_id
            WHERE i.status = %i
            GROUP BY id
            ORDER BY name
        ';
        return $this->getConnection()->query($sql, StockItem::STATUS_PALETTE)->fetchPairs('id', 'name');
    }
}
