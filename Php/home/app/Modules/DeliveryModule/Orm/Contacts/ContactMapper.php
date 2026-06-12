<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Contacts;

use App\Core\Orm\BaseMapper;

class ContactMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_contacts';
}
