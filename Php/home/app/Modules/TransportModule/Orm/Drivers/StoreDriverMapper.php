<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Drivers;

use App\Core\Orm\BaseMapper;

class StoreDriverMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'trans_store_drivers';
}
