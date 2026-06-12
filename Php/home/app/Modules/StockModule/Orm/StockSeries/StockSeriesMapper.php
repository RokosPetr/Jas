<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockSeries;

use App\Core\Orm\BaseMapper;

class StockSeriesMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_series';

    /** DB vazebni tabulka */
    public string $table_stock_series_stock_items = 'stock_series_items';

    public function loadStockSeries(): array
    {
        $sql = "
            SELECT i.reg_number, s.key
            FROM `stock_items` AS i
            JOIN `stock_series_items` AS si ON si.item_id = i.id
            JOIN `stock_series` AS s ON s.id = si.series_id
        ";

        $stockSeries = [];

        foreach ($this->getConnection()->query($sql)->fetchAll() as $row) {
            $stockSeries["$row->reg_number-$row->key"] = true;
        }

        return $stockSeries;
    }
}
