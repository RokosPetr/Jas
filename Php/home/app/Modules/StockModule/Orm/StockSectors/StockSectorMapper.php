<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockSectors;

use App\Core\Orm\BaseMapper;

class StockSectorMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_sectors';
}
