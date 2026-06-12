<?php
declare(strict_types=1);

namespace App\Modules\MtzModule\Orm\MtzOrders;

use App\Core\Orm\BaseMapper;

class MtzOrderMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'mtz_orders';
}
