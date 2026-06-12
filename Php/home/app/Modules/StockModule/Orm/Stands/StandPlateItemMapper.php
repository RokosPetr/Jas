<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Stands;

use App\Core\Orm\BaseMapper;

class StandPlateItemMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_stand_plate_items';
}
