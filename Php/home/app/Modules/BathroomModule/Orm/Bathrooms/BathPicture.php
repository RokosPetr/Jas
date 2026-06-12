<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Bathrooms;

use App\Core\Orm\BaseEntity;
use App\Modules\SystemModule\Orm\Files\File;

/**
 * @property int                       $id             {primary}
 * @property Bathroom                  $bathroom       {m:1 Bathroom::$pictures}
 * @property int                       $position
 * @property File                      $picture        {m:1 File, oneSided=true}
 */
class BathPicture extends BaseEntity
{
    public const PICTURE_3D_WIDTH = 2880;
    public const PICTURE_3D_QUALITY = 85;
}