<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Discounts;

use App\Core\Orm\BaseMapper;

class DiscountGroupMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_discount_groups';
}
