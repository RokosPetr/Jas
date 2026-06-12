<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\SalesData;

use App\Core\Orm\BaseMapper;

class SalesDataAccessMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_sales_data_access';
}
