<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DepotStandChecks;

use App\Core\Orm\BaseMapper;

class DepotStandCheckMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_stand_checks';
}
