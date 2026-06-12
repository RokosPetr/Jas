<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Companies;

use App\Core\Orm\BaseMapper;

class GroupMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_company_groups';
}
