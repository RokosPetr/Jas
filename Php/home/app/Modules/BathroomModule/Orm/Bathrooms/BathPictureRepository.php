<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Bathrooms;

use App\Core\Orm\BaseRepository;

class BathPictureRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [BathPicture::class];
    }

    public const MAX_POSITION = 6;
    public const POSITION_3D = 180;
    public const IMAGE_DIR = '/www/upload/bathrooms';
}