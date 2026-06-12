<?php
declare(strict_types=1);

namespace App\Modules\CliModule\Orm\Imports;

use App\Core\Orm\BaseMapper;

class ImportMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'sys_imports';
}
