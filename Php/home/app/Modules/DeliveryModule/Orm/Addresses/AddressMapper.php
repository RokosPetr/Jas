<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Addresses;

use App\Core\Orm\BaseMapper;

class AddressMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_depot_address';
}
