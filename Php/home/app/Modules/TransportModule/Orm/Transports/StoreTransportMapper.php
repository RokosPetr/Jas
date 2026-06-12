<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Transports;

use App\Core\Orm\BaseMapper;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use Nextras\Dbal\QueryBuilder\QueryBuilder;

class StoreTransportMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'trans_store_transports';

    public function findByLikeForDatagrid(array $filter, array $order = []): QueryBuilder
    {
        if (!empty($filter['validity'])) {
            unset($filter['validity']);
            $filter['id'] = $this->findInvalidTransports($filter['store->id'] ?? 0);
        }
        return parent::findByLikeForDatagrid($filter, $order);
    }

    public function findInvalidTransports(int $store): array
    {
        $sql = '
            SELECT DISTINCT temp.transport FROM (
                SELECT t.id AS transport,
                       tg.address,
                       tg.tariff,
                       tg.payment,
                       tgi.id AS item,
                       n.id AS note,
                       CONCAT(tgi.store, "-", tgi.delivery_note, "-", tgi.year) AS noteKey,
                       ABS(tgi.weight - ROUND(SUM(ni.amount * v.weight) / 1000)) AS weightDiff
                FROM `trans_store_transports` AS t
                JOIN `trans_store_transport_targets` AS tg ON tg.transport = t.id
                LEFT JOIN `trans_store_transport_items` AS tgi ON tgi.target = tg.id
                LEFT JOIN `delivery_notes` AS n ON n.number = tgi.delivery_note AND n.store = tgi.store AND YEAR(n.date) = tgi.year AND n.movement_type IN %i[]
                LEFT JOIN `delivery_note_items` AS ni ON ni.note = n.id
                LEFT JOIN `stock_variants` AS v ON v.id = ni.item
                WHERE t.deleted = 0
                AND t.store = %i
                GROUP BY tg.id, tgi.id               
            ) AS temp
            WHERE temp.address IS NULL
            OR temp.tariff IS NULL
            OR temp.payment IS NULL
            OR temp.item IS NULL
            OR temp.note IS NULL
            OR temp.weightDiff > 10
            OR temp.noteKey IN (
                SELECT CONCAT(temp2.store, "-", temp2.note, "-", temp2.year) FROM (
                    SELECT tgi2.store,
                           tgi2.year,
                           tgi2.delivery_note AS note,
                           CEILING(tgi2.weight / c.weight_capacity) AS minParts,
                           COUNT(tgip.id) AS totalParts
                    FROM `trans_store_transports` AS t2
                    JOIN `trans_store_cars` AS c ON c.id = t2.car
                    JOIN `trans_store_transport_targets` AS tg2 ON tg2.transport = t2.id
                    JOIN `trans_store_transport_items` AS tgi2 ON tgi2.target = tg2.id
                    JOIN `trans_store_transport_item_parts` AS tgip ON tgi2.id = tgip.item
                    WHERE t2.deleted = 0
                    AND t2.store = %i
                    GROUP BY tgi2.store, tgi2.year, tgi2.delivery_note          
                ) AS temp2
                WHERE temp2.minParts > temp2.totalParts
            )
        ';

        $params = [
            [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_TRANSFER_OUT],
            $store,
            $store
        ];

        return $this->getConnection()->query($sql, ...$params)->fetchPairs(null, 'transport');
    }
}
