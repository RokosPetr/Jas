<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\MainStorageOrders;

use App\Core\Orm\BaseMapper;

class MainStorageOrderItemMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_main_storage_order_items';
}
