<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Services;

use App\Core\Orm\BaseEntity;

/**
 * @property int                          $id               {primary}
 * @property int                          $number
 * @property string                       $name
 *
 * @property-read string                  $title            {virtual}
 */
class ServiceGroup extends BaseEntity
{
    public function getterTitle(): string
    {
        return "$this->number - $this->name";
    }
}
