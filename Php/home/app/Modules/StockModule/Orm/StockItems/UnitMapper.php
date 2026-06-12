<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockItems;

use App\Core\Orm\BaseMapper;

class UnitMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_units';
}
