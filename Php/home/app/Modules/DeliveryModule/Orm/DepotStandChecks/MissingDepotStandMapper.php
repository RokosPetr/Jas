<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DepotStandChecks;

use App\Core\Orm\BaseMapper;

class MissingDepotStandMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_missing_stands';
}
