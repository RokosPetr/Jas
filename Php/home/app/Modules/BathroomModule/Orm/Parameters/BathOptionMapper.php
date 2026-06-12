<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Parameters;

use App\Core\Orm\BaseMapper;

class BathOptionMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'bath_parameter_options';
}