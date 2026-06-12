<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Cubicles;

use App\Core\Orm\BaseMapper;

class CubicleItemMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_cubicle_items';
}
