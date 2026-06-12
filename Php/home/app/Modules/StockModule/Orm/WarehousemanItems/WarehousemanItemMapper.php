<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\WarehousemanItems;

use App\Core\Orm\BaseMapper;

class WarehousemanItemMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_warehouseman_items';

    public function deleteByYear(int $year): int
    {
        $dateFrom = "$year-01-01";
        $dateTo = "$year-12-31";

        $count = $this->getConnection()->queryByQueryBuilder(
            $this->builder()->where('`date` >= %s', $dateFrom)
                ->andWhere('`date` <= %s', $dateTo)
        )->count();

        $this->getConnection()->query(
            'DELETE FROM [stock_warehouseman_items] WHERE `date` >= %s AND `date` <= %s',
            $dateFrom,
            $dateTo
        );

        return $count;
    }

    public function loadItemsByDuration(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $sql = '
            SELECT j.id, j.web_id AS webId, j.name, j.deleted, sp.day_quantity AS quantity, sp.date, sp.length
            FROM (
                SELECT `id`, `name`, `web_id`, `created_at`, `deleted_at`, deleted FROM `stock_warehousemen` WHERE `created_at` <= %dt
                AND (`deleted_at` IS NULL OR `deleted_at` >= %dt)
            ) AS j
            LEFT JOIN (
                SELECT s.web_id, s.day_quantity, s.date, p.length
                FROM (
                    SELECT `web_id`, SUM(`quantity`) AS `day_quantity`, `date` FROM `stock_warehouseman_items`
                    WHERE `date` BETWEEN %dt AND %dt GROUP BY `date`, `web_id`
                ) AS s
                LEFT JOIN `stock_warehouseman_hours` AS p ON p.date = s.date
            ) AS sp ON sp.web_id = j.web_id AND j.created_at <= sp.date AND (j.deleted_at IS NULL OR sp.date < j.deleted_at)
            ORDER BY j.web_id, j.id DESC, sp.date
        ';

        return $this->getConnection()->query($sql, $to, $from, $from, $to)->fetchAll();
    }
}
