<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\ObligatoryItems;

use App\Core\Orm\BaseMapper;
use App\Modules\StockModule\Orm\StockItems\StockItem;
use App\Modules\StockModule\Orm\StockSeries\StockSeries;
use Nextras\Dbal\QueryBuilder\QueryBuilder;
use Nextras\Orm\Collection\ICollection;

class ObligatoryItemMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_obligatory_items';

    public function findByLikeForDatagrid(array $filter, array $order = []): QueryBuilder
    {
        if (isset($filter['producerId'])) {
            $producers = [];
            $globals = [];
            $producerFilter = is_array($filter['producerId']) ? $filter['producerId'] : [$filter['producerId']];
            unset($filter['producerId']);

            foreach ($producerFilter as $producerId) {
                if (isset(StockSeries::GLOBAL_SERIES[$producerId])) {
                    $globals[] = StockSeries::GLOBAL_SERIES[$producerId];
                } else {
                    $producers[] = $producerId;
                }
            }

            if ($globals && !$producers) {
                $filter['globalProducerId='] = $globals;
            } elseif (!$globals && $producers) {
                $filter['producerId='] = $producers;
                $filter['globalProducerId='] = null;
            } else {
                $filter['id='] = $this->getRepository()->findBy([
                    ICollection::OR,
                    'item->producer->id' => $producers,
                    'item->globalProducer' => $globals
                ])->fetchPairs(null, 'id');
            }
        }

        if (isset($filter['belowLimit'])) {
            $filter['belowLimit='] = $filter['belowLimit'];
            unset($filter['belowLimit']);
        }

        if (isset($filter['hasOrder'])) {
            $filter['hasOrder='] = $filter['hasOrder'];
            unset($filter['hasOrder']);
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
            FROM `stock_obligatory_items` AS oi
            JOIN `stock_items` AS i ON i.id = oi.item
            JOIN `stock_producers` AS p ON p.id = i.producer
            GROUP BY id, global_producer
            ORDER BY tiles DESC, name
        ';

        $result = $this->getConnection()->query($sql, StockItem::OSTRAVA_MAIN_STORAGE_PRODUCERS)->fetchAll();

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
            FROM `stock_obligatory_items` AS oi
            JOIN `stock_items` AS i ON i.id = oi.item
            JOIN `stock_series_items` AS si ON si.item_id = i.id
            JOIN `stock_series` AS s ON s.id = si.series_id
            GROUP BY id
            ORDER BY name
        ';
        return $this->getConnection()->query($sql)->fetchPairs('id', 'name');
    }
}
