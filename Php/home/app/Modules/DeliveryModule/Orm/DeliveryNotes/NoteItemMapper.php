<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DeliveryNotes;

use App\Core\Orm\BaseMapper;
use App\Core\Utils\DateTime;
use App\Modules\SystemModule\Orm\Stores\Store;

class NoteItemMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_note_items';

    /** Nahraje ceny za nakoupena mnozstvi daneho sortimentu od dodavatelu za dane obdobi ve vsech prodejnach */
    public function loadTakingsPriceData(array $stockGroups, \DateTimeInterface $from, \DateTimeInterface $till): array
    {
        $sql = '
            SELECT SUM(ni.amount * ni.buy_price) AS price, temp_n.producer
            FROM `delivery_note_items` AS ni
            RIGHT JOIN (
                SELECT n.id, p.id AS producer
                FROM `delivery_notes` AS n
                LEFT JOIN `delivery_note_items` AS ni ON n.id = ni.note
                LEFT JOIN `stock_variants` AS v ON ni.item = v.id
                LEFT JOIN `stock_items` AS i ON v.item = i.id
                LEFT JOIN `stock_groups` AS g ON i.group = g.id
                LEFT JOIN `stock_producers` AS p ON p.id = i.producer
                LEFT JOIN `delivery_company_depots` AS d ON d.id = n.depot
                LEFT JOIN `delivery_companies` AS c ON c.id = d.company
                WHERE g.id IN %i[]
                AND n.movement_type = %i
                AND n.date >= %s
                AND n.date <= %s
                AND (c.takings_ignore = 0 OR c.takings_ignore IS NULL)
                GROUP BY n.id
            ) AS temp_n ON temp_n.id = ni.note
            GROUP BY producer
        ';

        return $this->getConnection()->query(
            $sql,
            $stockGroups,
            DeliveryNote::TYPE_TAKINGS,
            $from->format(DateTime::DB_DATE),
            $till->format(DateTime::DB_DATE)
        )->fetchPairs('producer', 'price');
    }

    /** Nahraje ceny za nakoupena mnozstvi daneho sortimentu od dodavatele za dane obdobi ve vsech prodejnach */
    public function loadTakingsPriceDataPerProducer(array $producers, array $stockGroups, \DateTimeInterface $from, \DateTimeInterface $till): array
    {
        $sql = '
            SELECT temp_n.id, temp_n.number, SUM(ni.amount * ni.buy_price) AS sumValue
            FROM `delivery_note_items` AS ni
            RIGHT JOIN (
	            SELECT n.id, p.id AS producer, n.number
	            FROM `delivery_notes` AS n
	            LEFT JOIN `delivery_note_items` AS ni ON n.id = ni.note
	            LEFT JOIN `stock_variants` AS v ON ni.item = v.id
	            LEFT JOIN `stock_items` AS i ON v.item = i.id
	            LEFT JOIN `stock_groups` AS g ON i.group = g.id
	            LEFT JOIN `stock_producers` AS p ON p.id = i.producer
	            LEFT JOIN `delivery_company_depots` AS d ON d.id = n.depot
                LEFT JOIN `delivery_companies` AS c ON c.id = d.company
	            WHERE g.id IN %i[]
	            AND n.movement_type = %i
	            AND n.date >= %s
	            AND n.date <= %s
	            AND p.id IN %i[]
                AND (c.takings_ignore = 0 OR c.takings_ignore IS NULL)
	            GROUP BY n.id
	        ) AS temp_n ON temp_n.id = ni.note
            GROUP BY temp_n.id
            ORDER BY number
        ';

        return $this->getConnection()->query(
            $sql,
            $stockGroups,
            DeliveryNote::TYPE_TAKINGS,
            $from->format(DateTime::DB_DATE),
            $till->format(DateTime::DB_DATE),
            $producers
        )->fetchAll();
    }

    /** Nahraje mnozstvi m2 daneho sortimentu od dodavatelu za dane obdobi ve vsech prodejnach */
    public function loadSquareMetersTakingsData(array $stockGroups, \DateTimeInterface $from, \DateTimeInterface $till): array
    {
        $sql = '
            SELECT p.id, SUM(ni.amount) AS amount
            FROM `delivery_note_items` AS ni
            LEFT JOIN `delivery_notes` AS n ON n.id = ni.note
            LEFT JOIN `stock_variants` AS v ON ni.item = v.id
            LEFT JOIN `stock_items` AS i ON v.item = i.id
            LEFT JOIN `stock_units` AS u ON u.id = i.unit
            LEFT JOIN `stock_groups` AS g ON i.group = g.id
            LEFT JOIN `stock_producers` AS p ON p.id = i.producer
            LEFT JOIN `delivery_company_depots` AS d ON d.id = n.depot
            LEFT JOIN `delivery_companies` AS c ON c.id = d.company
            WHERE g.id IN %i[]
            AND n.movement_type = %i
            AND u.name = "m2"
            AND n.date >= %s
            AND n.date <= %s
            AND ni.buy_price > 0.1
            AND (c.takings_ignore = 0 OR c.takings_ignore IS NULL)
            GROUP BY id
        ';

        return $this->getConnection()->query(
            $sql,
            $stockGroups,
            DeliveryNote::TYPE_TAKINGS,
            $from->format(DateTime::DB_DATE),
            $till->format(DateTime::DB_DATE)
        )->fetchPairs('id', 'amount');
    }

    /** Nahraje mnozstvi m2 daneho sortimentu od dodavatele za dane obdobi ve vsech prodejnach */
    public function loadSquareMetersTakingsDataPerProducer(array $producers, array $stockGroups, \DateTimeInterface $from, \DateTimeInterface $till): array
    {
        $sql = '
            SELECT n.id, n.number, SUM(ni.amount) AS sumValue
            FROM `delivery_note_items` AS ni
            LEFT JOIN `delivery_notes` AS n ON n.id = ni.note
            LEFT JOIN `stock_variants` AS v ON ni.item = v.id
            LEFT JOIN `stock_items` AS i ON v.item = i.id
            LEFT JOIN `stock_units` AS u ON u.id = i.unit
            LEFT JOIN `stock_groups` AS g ON i.group = g.id
            LEFT JOIN `stock_producers` AS p ON p.id = i.producer
            LEFT JOIN `delivery_company_depots` AS d ON d.id = n.depot
            LEFT JOIN `delivery_companies` AS c ON c.id = d.company
            WHERE g.id IN %i[]
            AND n.movement_type = %i
            AND u.name = "m2"
            AND n.date >= %s
	        AND n.date <= %s
            AND ni.buy_price > 0.1
            AND p.id IN %i[]
            AND (c.takings_ignore = 0 OR c.takings_ignore IS NULL)
            GROUP BY n.id
            ORDER BY n.number
        ';

        return $this->getConnection()->query(
            $sql,
            $stockGroups,
            DeliveryNote::TYPE_TAKINGS,
            $from->format(DateTime::DB_DATE),
            $till->format(DateTime::DB_DATE),
            $producers
        )->fetchAll();
    }

    /** Nahraje cenu za palety, ktere jsou jako jedina polozka na DL za zvolene obdobi */
    public function loadEmptyPalettesTakingsData(\DateTimeInterface $from, \DateTimeInterface $till): array
    {
        $sql = '
            SELECT temp_g.id
            FROM `stock_groups` AS temp_g
            LEFT JOIN `stock_producers` AS temp_p ON temp_p.id = temp_g.producer
            WHERE temp_g.number = 1
            AND temp_p.number = 31
        ';
        $paletteGroup = $this->getConnection()->query($sql)->fetchPairs(null, 'id')[0];

        $sql = '
            SELECT temp.producer, SUM(temp.price) AS price
            FROM (
                SELECT n.number, COUNT(ni.id) as item_count, g.id AS group_id, (ni.amount * ni.buy_price) AS price, p.id AS producer
                FROM `delivery_notes` AS n
                LEFT JOIN `delivery_note_items` AS ni ON n.id = ni.note
                LEFT JOIN `stock_variants` AS v ON ni.item = v.id
                LEFT JOIN `stock_items` AS i ON v.item = i.id
                LEFT JOIN `stock_groups` AS g ON i.group = g.id
                LEFT JOIN `delivery_company_depots` AS d ON d.id = n.depot
                LEFT JOIN `delivery_companies` AS c ON c.id = d.company
                INNER JOIN `stock_producers` AS p ON p.company = c.id
                WHERE n.movement_type = %i
                AND n.date >= %s
                AND n.date <= %s
                GROUP BY n.id
            ) AS temp
            WHERE temp.item_count = 1
            AND temp.group_id = %i
            GROUP BY temp.producer
        ';

        return $this->getConnection()->query(
            $sql,
            DeliveryNote::TYPE_TAKINGS,
            $from->format(DateTime::DB_DATE),
            $till->format(DateTime::DB_DATE),
            $paletteGroup
        )->fetchPairs('producer', 'price');
    }

    /** Nahraje cenu za palety, ktere jsou jako jedina polozka na DL od dodavatele za zvolene obdobi */
    public function loadEmptyPalettesTakingsDataPerProducer(array $producers, \DateTimeInterface $from, \DateTimeInterface $till): array
    {
        $sql = '
            SELECT temp_g.id
            FROM `stock_groups` AS temp_g
            LEFT JOIN `stock_producers` AS temp_p ON temp_p.id = temp_g.producer
            WHERE temp_g.number = 1
            AND temp_p.number = 31
        ';
        $paletteGroup = $this->getConnection()->query($sql)->fetchPairs(null, 'id')[0];

        $sql = '
            SELECT temp.id, temp.number, SUM(temp.price) AS sumValue
            FROM (
                SELECT n.id, n.number, COUNT(ni.id) as item_count, g.id AS group_id, (ni.amount * ni.buy_price) AS price
                FROM `delivery_notes` AS n
                LEFT JOIN `delivery_note_items` AS ni ON n.id = ni.note
                LEFT JOIN `stock_variants` AS v ON ni.item = v.id
                LEFT JOIN `stock_items` AS i ON v.item = i.id
                LEFT JOIN `stock_groups` AS g ON i.group = g.id
                LEFT JOIN `delivery_company_depots` AS d ON d.id = n.depot
                LEFT JOIN `delivery_companies` AS c ON c.id = d.company
                INNER JOIN `stock_producers` AS p ON p.company = c.id
                WHERE n.movement_type = %i
                AND n.date >= %s
                AND n.date <= %s
                AND p.id IN %i[]
                GROUP BY n.id
            ) AS temp
            WHERE temp.item_count = 1
            AND temp.group_id = %i
            GROUP BY temp.id
        ';

        return $this->getConnection()->query(
            $sql,
            DeliveryNote::TYPE_TAKINGS,
            $from->format(DateTime::DB_DATE),
            $till->format(DateTime::DB_DATE),
            $producers,
            $paletteGroup
        )->fetchAll();
    }

    /** Nahraje nakoupena mnozstvi daneho sortimentu od dodavatelu za dane obdobi v jednotlivych pobockach */
    public function loadStoreTakingsData(array $stockGroups, \DateTimeInterface $from, \DateTimeInterface $till): array
    {
        $sql = '
            SELECT
                s.id AS store,
                p.id as producer,
                n.movement_type,
                SUM(ni.buy_price * ni.amount) AS price
            FROM `delivery_note_items` AS ni
            LEFT JOIN `delivery_notes` AS n ON n.id = ni.note
            LEFT JOIN `sys_stores` AS s ON s.id = n.store
            LEFT JOIN `stock_variants` AS v ON ni.item = v.id
            LEFT JOIN `stock_items` AS i ON v.item = i.id
            LEFT JOIN `stock_groups` AS g ON i.group = g.id
            LEFT JOIN `stock_producers` AS p ON p.id = i.producer
            LEFT JOIN `delivery_company_depots` AS d ON n.depot = d.id
            WHERE g.id IN %i[]
            AND n.movement_type IN %i[]
            AND n.date >= %s
            AND n.date <= %s
            AND (
                n.movement_type = 4
                OR d.voj <> 11
            )
            AND (
                n.movement_type = %i
                OR d.voj IN %s[]
            )
            AND (
                n.movement_type = %i
                OR s.id NOT IN %i[]
            )
            AND (
                n.movement_type = %i
                OR p.no_transfers = 0
            )
            AND (
                n.movement_type = %i
                OR g.no_transfers = 0
            )
            GROUP BY producer, store, movement_type
        ';

        $result = $this->getConnection()->query(
            $sql,
            $stockGroups,
            [DeliveryNote::TYPE_TAKINGS],//, DeliveryNote::TYPE_TRANSFER_IN],
            $from->format(DateTime::DB_DATE),
            $till->format(DateTime::DB_DATE),
            DeliveryNote::TYPE_TAKINGS,
            [(string) Store::OSTRAVA_MAIN_STORAGE, (string) Store::HLUCIN_MAIN_STORAGE, (string) Store::LC_MAIN_STORAGE],
            DeliveryNote::TYPE_TAKINGS,
            Store::MAIN_STORAGES,
            DeliveryNote::TYPE_TAKINGS,
            DeliveryNote::TYPE_TAKINGS
        )->fetchAll();

        $takings = [];

        foreach ($result as $row) {
            // Velkosklady se pocataji jako jedna entita
            if (!isset($takings[$row->producer][Store::OSTRAVA_MAIN_STORAGE])) {
                $takings[$row->producer][Store::OSTRAVA_MAIN_STORAGE] = 0;
            }

            if (in_array($row->store, Store::MAIN_STORAGES)) {
                $takings[$row->producer][Store::OSTRAVA_MAIN_STORAGE] += $row->price;
            } else {
                if (!isset($takings[$row->producer][$row->store])) {
                    $takings[$row->producer][$row->store] = 0;
                }

                $takings[$row->producer][$row->store] += $row->price;

                //Analýza nákupů poboček 20251031
                /*if ($row->movement_type === DeliveryNote::TYPE_TRANSFER_IN) {
                    // Prevodky se odecitaji od prijmu velkoskladu
                    $takings[$row->producer][Store::OSTRAVA_MAIN_STORAGE] -= $row->price;
                }*/
            }
        }

        return $takings;
    }

    /** Nahraje nakoupena mnozstvi daneho sortimentu od dodavatelu za dane obdobi v jednotlivych pobockach */
    public function loadStoreTakingsDataPerProducer(array $producers, array $stockGroups, \DateTimeInterface $from, \DateTimeInterface $till): array
    {
        $sql = '
            SELECT
                n.id,
                n.number,
                n.movement_type,
                s.id AS store,
                SUM(ni.buy_price * ni.amount) AS sumValue
            FROM `delivery_note_items` AS ni
            LEFT JOIN `delivery_notes` AS n ON n.id = ni.note
            LEFT JOIN `sys_stores` AS s ON s.id = n.store
            LEFT JOIN `stock_variants` AS v ON ni.item = v.id
            LEFT JOIN `stock_items` AS i ON v.item = i.id
            LEFT JOIN `stock_groups` AS g ON i.group = g.id
            LEFT JOIN `stock_producers` AS p ON p.id = i.producer
            LEFT JOIN `delivery_company_depots` AS d ON n.depot = d.id
            WHERE g.id IN %i[]
            AND n.movement_type IN %i[]
            AND n.date >= %s
            AND n.date <= %s
            AND p.id IN %i[]
            AND (
                n.movement_type = %i
                OR d.voj IN %s[]
            )
            AND (
                n.movement_type = %i
                OR s.id NOT IN %i[]
            )
            AND (
                n.movement_type = %i
                OR p.no_transfers = 0
            )
            AND (
                n.movement_type = %i
                OR g.no_transfers = 0
            )
            GROUP BY n.id
            ORDER BY n.number
        ';

        return $this->getConnection()->query(
            $sql,
            $stockGroups,
            [DeliveryNote::TYPE_TAKINGS, DeliveryNote::TYPE_TRANSFER_IN],
            $from->format(DateTime::DB_DATE),
            $till->format(DateTime::DB_DATE),
            $producers,
            DeliveryNote::TYPE_TAKINGS,
            [(string) Store::OSTRAVA_MAIN_STORAGE, (string) Store::HLUCIN_MAIN_STORAGE],
            DeliveryNote::TYPE_TAKINGS,
            Store::MAIN_STORAGES,
            DeliveryNote::TYPE_TAKINGS,
            DeliveryNote::TYPE_TAKINGS
        )->fetchAll();
    }
}
