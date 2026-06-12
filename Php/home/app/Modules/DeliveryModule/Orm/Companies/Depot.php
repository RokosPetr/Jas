<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Companies;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbString;
use App\Modules\DeliveryModule\Orm\Addresses\Address;
use App\Modules\DeliveryModule\Orm\Contacts\Contact;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\DeliveryModule\Orm\DepotStandChecks\DepotStandCheck;
use App\Modules\StockModule\Orm\Discounts\DiscountGroup;
use App\Modules\StockModule\Orm\Stands\Stand;
use App\Modules\StockModule\Orm\Stands\StandNote;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Modules\SystemModule\Orm\Users\User;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Relationships\ManyHasMany;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                            $id               {primary}
 * @property Store                          $store            {m:1 Store, oneSided=true}
 * @property Company                        $company          {m:1 Company::$depots}
 * @property string                         $voj
 * @property string                         $title
 * @property string                         $city
 * @property array                          $information
 * @property Group|null                     $group            {m:1 Group::$depots}
 * @property DiscountGroup|null             $discountGroup    {m:1 DiscountGroup::$depots}
 * @property OneHasMany|DeliveryNote[]      $deliveries       {1:m DeliveryNote::$depot}
 * @property ManyHasMany|User[]             $dealers          {m:m User::$depots, isMain=true}
 * @property OneHasMany|StandNote[]         $standNotes       {1:m StandNote::$depot}
 * @property OneHasMany|DepotStandCheck[]   $standChecks      {1:m DepotStandCheck::$depot}
 * @property OneHasMany|Contact[]           $contacts         {1:m Contact::$depot}
 * @property Address|null                   $contactAddress   {1:1 Address::$depot}
 *
 * @property-read string                    $name             {virtual}
 * @property-read string                    $companyName      {virtual}
 * @property-read string                    $companyIcoString {virtual}
 * @property-read DepotStandCheck|null      $lastStandCheck   {virtual}
 * @property-read string                    $depotName        {virtual}
 * @property-read string                    $groupNumber      {virtual}
 * @property-read string                    $address          {virtual}
 * @property-read bool                      $hasDiscounts     {virtual}
 */
class Depot extends BaseEntity
{
    public function getterAddress(): string
    {
        $addressData = [];
        foreach (['street', 'city', 'zipCode'] as $addressPart) {
            if (!empty($this->information[$addressPart])) {
                $addressData[] = $this->information[$addressPart];
            }
        }
        return $addressData ? implode(PHP_EOL, $addressData) : '';
    }

    public function getterHasDiscounts(): bool
    {
        return !is_null($this->discountGroup);
    }

    public function getterName(): string
    {
        $name = $this->company->name;
        if ($this->title) {
            $name .= " ($this->title)";
        }
        return $name;
    }

    public function getSqlName(): DbFunction
    {
        return new DbFunction(
            'CONCAT_WS',
            new DbColumn('company->name'),
            new DbString("' '"),
            new DbColumn('title')
        );
    }

    public function getterCompanyIcoString(): string
    {
        return $this->company->icoString;
    }

    public function getterDepotName(): string
    {
        $name = $this->company->companyName;
        if ($this->title) {
            $name .= " ($this->title)";
        }
        return $name;
    }

    public function getterGroupNumber(): string
    {
        return $this->group ? $this->group->numberString : '00';
    }

    public function getSqlCompanyIcoString(): DbFunction
    {
        return new DbFunction(
            'LPAD',
            new DbColumn('company->ico'),
            new DbString('8'),
            new DbString("'0'")
        );
    }

    public function getterCompanyName(): string
    {
        return $this->company->name;
    }

    public function getSqlCompanyName(): DbColumn
    {
        return new DbColumn('company->name');
    }

    public function getSqlStore(): DbColumn
    {
        return new DbColumn('store->id');
    }

    public function getSqlGroup(): DbColumn
    {
        return new DbColumn('group->number');
    }

    public function getSqlDealers(): DbColumn
    {
        return new DbColumn('dealers->id');
    }

    public function getterLastStandCheck(): ?DepotStandCheck
    {
        return $this->standChecks->toCollection()->orderBy('createdAt', ICollection::DESC)->fetch();
    }

    /** @return StandNote[] */
    public function loadCurrentStandNotes(bool $deliveredOnly = true): array
    {
        $filter = ['stand->deleted' => false, 'removeDate' => null];
        if ($deliveredOnly) {
            $filter['date<'] = new \DateTime();
        }
        return $this->standNotes->toCollection()->findBy($filter)->orderBy('date')->fetchPairs('id');
    }

    public function hasStand(Stand $stand): bool
    {
        return $this->standNotes->toCollection()->findBy([
            'stand->id' => $stand->id, 'removeDate' => null
        ])->countStored() > 0;
    }
}
