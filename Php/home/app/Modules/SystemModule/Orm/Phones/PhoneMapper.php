<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Phones;

use App\Core\Orm\BaseMapper;

class PhoneMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'sys_phones';
}
