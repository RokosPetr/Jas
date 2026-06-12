<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Addresses;

use App\Core\Orm\BaseEntity;
use App\Modules\DeliveryModule\Orm\Companies\Depot;

/**
 * @property int                            $id               {primary}
 * @property Depot                          $depot            {1:1 Depot::$contactAddress, isMain=true}
 * @property string                         $street
 * @property string                         $number
 * @property string                         $city
 * @property string                         $zip
 * @property string                         $district
 * @property string|null                    $openHours
 * @property string|null                    $billingEmail
 * @property string|null                    $complainEmail
 *
 * @property-read string                    $title            {virtual}
 */
class Address extends BaseEntity
{
    public function getterTitle(): ?string
    {
        $addressData = [];

        if ($this->street) {
            $addressData[] = $this->street;
            $addressData[] = $this->number;
            $addressData[] = $this->city;
        } else {
            $addressData[] = $this->city;
            $addressData[] = $this->number;
        }

        $addressData[] = $this->zip;

        return implode(', ', $addressData);
    }
}
