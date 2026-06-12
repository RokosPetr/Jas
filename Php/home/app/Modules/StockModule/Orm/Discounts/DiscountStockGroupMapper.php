<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Discounts;

use App\Core\Orm\BaseMapper;

class DiscountStockGroupMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_discount_stock_groups';
}
