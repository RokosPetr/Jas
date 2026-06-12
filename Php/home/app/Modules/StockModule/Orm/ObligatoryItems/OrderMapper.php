<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\ObligatoryItems;

use App\Core\Orm\BaseMapper;

class OrderMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_obligatory_item_orders';
}
