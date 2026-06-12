<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockGroups;

use App\Core\Orm\BaseMapper;

class StockGroupMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_groups';
}
