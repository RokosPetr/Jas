<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DeliveryItems;

use App\Core\Orm\BaseMapper;

class DeliveryItemMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_item';
}
