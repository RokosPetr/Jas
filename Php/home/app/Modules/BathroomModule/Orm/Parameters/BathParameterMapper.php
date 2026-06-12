<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Parameters;

use App\Core\Orm\BaseMapper;

class BathParameterMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'bath_parameters';
}