<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Mails;

use App\Core\Orm\BaseMapper;

class MailMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'sys_mails';
}
