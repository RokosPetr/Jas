<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Transports;

use App\Core\Orm\BaseMapper;

class StoreTransportTargetMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'trans_store_transport_targets';
}
