<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Bathrooms;

use App\Core\Orm\BaseMapper;

class BathPictureMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'bath_pictures';
}