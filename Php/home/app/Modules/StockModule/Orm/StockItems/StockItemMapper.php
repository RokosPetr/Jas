<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockItems;

use App\Core\Orm\BaseMapper;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\StockModule\Orm\StockSeries\StockSeries;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nextras\Dbal\QueryBuilder\QueryBuilder;
use Nextras\Orm\Collection\ICollection;

class StockItemMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_items';

    public function findByLikeForDatagrid(array $filter, array $order = []): QueryBuilder
    {
        if (isset($filter['producer'])) {
            $producers = [];
            $globals = [];
            $producerFilter = is_array($filter['producer']) ? $filter['producer'] : [$filter['producer']];
            unset($filter['producer']);

            foreach ($producerFilter as $producerId) {
                if (isset(StockSeries::GLOBAL_SERIES[$producerId])) {
                    $globals[] = StockSeries::GLOBAL_SERIES[$producerId];
                } else {
                    $producers[] = $producerId;
                }
            }

            if ($globals && !$producers) {
                $filter['globalProducer'] = $globals;
            } elseif (!$globals && $producers) {
                $filter['producer'] = $producers;
                $filter['globalProducer'] = null;
            } else {
                $filter['id='] = $this->getRepository()->findBy([
                    ICollection::OR,
                    'producer->id' => $producers,
                    'globalProducer' => $globals
                ])->fetchPairs(null, 'id');
            }
        }

        if (empty($filter['status'])) {
            $filter['status!='] = StockItem::STATUS_DISCARDED;
        } elseif ($filter['status'] === 'all') {
            $filter['status'] = '';
        } else {
            $filter['status='] = $filter['status'];
        }
        unset($filter['status']);
        return parent::findByLikeForDatagrid($filter, $order);
    }

    public function loadSales(int $itemId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $sql = '
            SELECT di.id, di.amount FROM `delivery_note_items` AS di
            LEFT JOIN `delivery_notes` AS n ON n.id = di.note
            LEFT JOIN `stock_variants` AS v ON v.id = di.item
            LEFT JOIN `stock_items` AS i ON i.id = v.item
            WHERE i.id = %i
            AND n.movement_type = %i
            AND n.state != %i
            AND n.date >= %dt
            AND n.date <= %dt
        ';

        $params = [$itemId, DeliveryNote::TYPE_SALE, DeliveryNote::STATE_RESERVATION, $from, $to];

        return $this->getConnection()->query($sql, ...$params)->fetchPairs('id', 'amount');
    }

    public function loadCancels(int $itemId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $sql = '
            SELECT di.id, di.amount FROM `delivery_note_items` AS di
            LEFT JOIN `delivery_notes` AS n ON n.id = di.note
            LEFT JOIN `stock_variants` AS v ON v.id = di.item
            LEFT JOIN `stock_items` AS i ON i.id = v.item
            WHERE i.id = %i
            AND n.movement_type = %i
            AND n.date >= %dt
            AND n.date <= %dt
        ';

        $params = [$itemId, DeliveryNote::TYPE_CANCEL, $from, $to];

        return $this->getConnection()->query($sql, ...$params)->fetchPairs('id', 'amount');
    }

    public function loadGroupId(): array
    {
        $sql = '
            SELECT si.reg_number, sg.id AS `group` 
            FROM `stock_items` si
            INNER JOIN `stock_groups` sg ON sg.id = si.`group`
        ';


        return $this->getConnection()->query($sql)->fetchPairs('reg_number', 'group');
    }

    public function loadExportData(array $filter, array $order): array
    {
        $builder = $this->findByLikeForDatagrid($filter)->groupBy($this->getTableName() . '.id');
        $result = $this->connection->queryArgs(
            $builder->getQuerySql(),
            $builder->getQueryParameters()
        );
        $itemIds = $result->fetchPairs(null, 'id');
        $validOrderColumns = [
            'regNumber', 'name', 'producer', 'statusChangedAt', 'minOrder', 'palette', 'package'
        ];
        $orderBy = in_array($order[0] ?? false, $validOrderColumns) ? $order[0] : 'regNumber';
        $orderDirection = ($order[1] === ICollection::DESC ? ICollection::DESC : ICollection::ASC);

        $sql = "
            SELECT i.reg_number AS regNumber,
                   i.name,
                   p.name AS producer,
                   p.number AS producerNumber,
                   CONCAT(g.number, ' - ', g.name) AS groupName,
                   c.name AS storageCatalog,
                   u.name AS unit,
                   GROUP_CONCAT(DISTINCT s.name SEPARATOR ', ') AS seriesName,
                   i.price,
                   i.palette,
                   i.package,
                   i.min_order AS minOrder,
                   i.status,
                   i.status_changed_at AS statusChangedAt,
                   MAX(v.quantity) AS OstravaQuantity,
                   MAX(v2.quantity) AS HlucinQuantity
            FROM `stock_items` AS i
            LEFT JOIN `stock_producers` AS p ON i.producer = p.id
            LEFT JOIN `stock_groups` AS g ON i.group = g.id
            LEFT JOIN `stock_units` AS u ON i.unit = u.id
            LEFT JOIN `stock_series_items` AS si ON si.item_id = i.id
            LEFT JOIN `stock_series` AS s ON si.series_id = s.id
            LEFT JOIN `stock_variants` AS v ON v.item = i.id AND v.store = %i
            LEFT JOIN `stock_catalog_numbers` AS c ON c.id = v.catalog
            LEFT JOIN `stock_variants` AS v2 ON v2.item = i.id AND v.store = %i
            WHERE i.id IN %i[]
            GROUP BY i.id
            ORDER BY $orderBy $orderDirection
        ";

        return $this->connection->query(
            $sql,
            Store::OSTRAVA_MAIN_STORAGE,
            Store::HLUCIN_MAIN_STORAGE,
            $itemIds
        )->fetchAll();
    }
}
