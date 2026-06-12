<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Parameters;

use App\Core\Orm\BaseEntity;
use App\Modules\BathroomModule\Orm\Bathrooms\Bathroom;
use App\Modules\SystemModule\Orm\Files\File;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * @property int                       $id             {primary}
 * @property BathParameter             $parameter      {m:1 BathParameter::$options}
 * @property string                    $name
 * @property string|null               $description
 * @property int                       $order
 * @property File|null                 $picture        {m:1 File, oneSided=true}
 * @property string|null               $color
 *
 * @property ManyHasMany|Bathroom[]    $bathrooms      {m:m Bathroom::$options}
 *
 * @property-read bool                 $hasItems       {virtual}
 */
class BathOption extends BaseEntity
{
    public function getterHasItems(): bool
    {
        return $this->bathrooms->count() > 0;
    }
}