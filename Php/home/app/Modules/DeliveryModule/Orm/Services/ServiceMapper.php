<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Services;

use App\Core\Orm\BaseMapper;

class ServiceMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_services';
}
