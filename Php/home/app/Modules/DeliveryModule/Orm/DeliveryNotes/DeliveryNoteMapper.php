<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DeliveryNotes;

use App\Core\Orm\BaseMapper;
use App\Core\Utils\DateTime;

class DeliveryNoteMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_notes';

    public function loadByCurrentSeason(int $storeId, int $season = null): array
    {
        $sql = '
            SELECT CONCAT(number, "' . self::DATA_STRING_SEPARATOR . '", movement_number, "' . self::DATA_STRING_SEPARATOR . '", date) as dataId, id
            FROM `delivery_notes`
        ';

        if ($season) {
            $sql .= ' WHERE `store` = %i AND (`season` = %i OR `season` IS NULL)';
            return $this->getConnection()->query($sql, $storeId, $season)->fetchPairs('dataId', 'id');
        }

        $sql .= ' WHERE `store` = %i AND `season` IS NULL';
        return $this->getConnection()->query($sql, $storeId)->fetchPairs('dataId', 'id');
    }

    public function deleteByCurrentSeason(int $storeId, int $season = null): void
    {
        $sql = 'DELETE FROM `delivery_notes` WHERE `store` = %i AND ';

        if (!$season) {
            $sql .= '`season` IS NULL';
            $this->getConnection()->query($sql, $storeId);
            return;
        }

        $sql .= '(`season` = %i OR `season` IS NULL)';
        $this->getConnection()->query($sql, $storeId, $season);
    }

    public function deleteBySeason(int $storeId, int $season): void
    {
        $sql = 'DELETE FROM `delivery_notes` WHERE `store` = %i AND `season` = %i';
        $this->getConnection()->query($sql, $storeId, $season);
    }

    public function loadDuplicities(int $year = null): array
    {
        $result = [];
        $sql = '
            SELECT temp.store, temp.number
            FROM (
               SELECT COUNT(`id`) AS id_count,
               ROUND(`season` / 100) AS year,
               `store`, `number`, `movement_number`, `movement_type`
               FROM `delivery_notes`
               GROUP BY year, store, number, movement_number
            ) AS temp
            WHERE temp.id_count > 1
            AND temp.movement_type != %i
            AND temp.year = %i
        ';

        $rows = $this->getConnection()->query($sql, DeliveryNote::TYPE_CANCEL, $year ?? intval(date('Y')));

        foreach ($rows as $row) {
            if (!isset($result[$row->store])) {
                $result[$row->store] =  [];
            }

            $result[$row->store][] = $row->number;
        }

        return $result;
    }

    public function loadBadTransfers(int $store): array
    {
        // Rozdil prevodek muze byt +- 10 kc
        $sql = '
            SELECT trIn.id FROM (
                SELECT n.id, n.parent, SUM(ni.buy_price * ni.amount) AS buySum, SUM(ni.sell_price * ni.amount) AS sellSum
                FROM `delivery_notes` AS n
                JOIN `delivery_note_items` AS ni ON ni.note = n.id
                JOIN `delivery_company_depots` AS d ON d.id = n.depot
                WHERE n.movement_type = %i
                AND n.store = %i
                AND n.checked = 0
                AND d.voj != "88"
                and year(n.date) = 2024
                GROUP BY n.id
            ) AS trIn
            LEFT JOIN (
                SELECT n.id, SUM(ni.buy_price * ni.amount) AS buySum, SUM(ni.sell_price * ni.amount) AS sellSum
                FROM `delivery_notes` AS n
                JOIN `delivery_note_items` AS ni ON ni.note = n.id
                JOIN `delivery_company_depots` AS d ON d.id = n.depot
                WHERE n.movement_type = %i
                AND d.voj != "88"
                and year(n.date) = 2024
                GROUP BY n.id
            ) AS trOut ON trIn.parent = trOut.id
            WHERE trIn.parent IS NULL OR ABS(trIn.buySum - trOut.buySum) > %i OR ABS(trIn.sellSum - trOut.sellSum) > %i
            ORDER BY id
        ';

        $return = $this->getConnection()->query(
            $sql,
            DeliveryNote::TYPE_TRANSFER_IN,
            $store,
            DeliveryNote::TYPE_TRANSFER_OUT,
            DeliveryNote::TRANSFER_SUM_PRECISION,
            DeliveryNote::TRANSFER_SUM_PRECISION
        )->fetchPairs(null, 'id');

        return $return;
    }
}
