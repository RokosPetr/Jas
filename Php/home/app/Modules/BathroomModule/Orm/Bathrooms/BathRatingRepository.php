<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Bathrooms;

use App\Core\Orm\BaseRepository;

class BathRatingRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [BathRating::class];
    }
}