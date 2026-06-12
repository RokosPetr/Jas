<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Warehousemen;

use App\Core\Orm\BaseMapper;

class WarehousemanMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_warehousemen';
}
