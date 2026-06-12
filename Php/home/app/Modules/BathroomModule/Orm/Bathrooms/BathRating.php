<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Bathrooms;

use App\Core\Orm\BaseEntity;
use Nextras\Dbal\Utils\DateTimeImmutable;

/**
 * @property int                       $id             {primary}
 * @property Bathroom                  $bathroom       {m:1 Bathroom::$ratings}
 * @property int                       $rating
 * @property DateTimeImmutable         $date           {default now}
 */
class BathRating extends BaseEntity
{

}