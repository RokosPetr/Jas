<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Files;

use App\Core\Orm\BaseMapper;

class FileMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'sys_files';
}
