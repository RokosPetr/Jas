<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Bathrooms;

use App\Core\Orm\BaseMapper;

class BathroomMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'bath_bathrooms';

    /** DB vazebni tabulka */
    public string $table_bath_bathrooms_bath_parameter_options = 'bath_bathroom_options';
}