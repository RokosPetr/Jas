<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Companies;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbString;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                            $id               {primary}
 * @property int                            $ico
 * @property string                         $name
 * @property array                          $information
 * @property string                         $countryCode
 * @property OneHasMany|Depot[]             $depots           {1:m Depot::$company}
 * @property bool                           $takingsIgnore    {default false}
 *
 * @property-read string                    $address          {virtual}
 * @property-read string                    $icoString        {virtual}
 * @property-read string                    $storeIds         {virtual}
 * @property-read string                    $companyName      {virtual}
 */
class Company extends BaseEntity
{
    public function getterAddress(): string
    {
        $addressData = [];
        foreach (['street', 'city', 'zipCode'] as $addressPart) {
            if (!empty($this->information[$addressPart])) {
                $addressData[] = $this->information[$addressPart];
            }
        }
        return $addressData ? implode(', ', $addressData) : '-';
    }

    public function getterStoreIds(): string
    {
        return implode(', ', array_unique($this->depots->toCollection()->orderBy('store->id')->fetchPairs(null, 'store->id')));
    }

    public function getterIcoString(): string
    {
        return str_pad((string) $this->ico, 8, '0', STR_PAD_LEFT);
    }

    public function getSqlIcoString(): DbFunction
    {
        return new DbFunction(
            'LPAD',
            new DbColumn('ico'),
            new DbString('8'),
            new DbString("'0'")
        );
    }

    public function getterCompanyName(): string
    {
        return "$this->icoString - $this->name";
    }
}
