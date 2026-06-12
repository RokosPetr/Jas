<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Producers;

use App\Core\Orm\BaseEntity;
use App\Modules\DeliveryModule\Orm\Companies\Company;
use App\Modules\StockModule\Orm\StockGroups\StockGroup;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                          $id             {primary}
 * @property int                          $number
 * @property string                       $name
 * @property Producer|null                $parent         {m:1 Producer::$children}
 * @property OneHasMany|Producer[]        $children       {1:m Producer::$parent}
 * @property OneHasMany|StockGroup[]      $stockGroups    {1:m StockGroup::$producer}
 * @property Company|null                 $company        {m:1 Company, oneSided=true}
 * @property bool                         $noTransfers    {default false}
 * @property string                       $color          {default '#000000'}
 *
 * @property-read bool                    $isMainProducer {virtual}
 * @property-read string                  $title          {virtual}
 */
class Producer extends BaseEntity
{
    public const DC_RAVAK_ID = 151;
    public const DC_RAVAK_GROUPS = [1, 2, 25, 45, 46, 50];
    public const RAVAK_NAME = 'Ravak';

    public function getterIsMainProducer(): bool
    {
        return $this->children->count() > 0;
    }

    public function getterTitle(): string
    {
        return "$this->number - $this->name";
    }
}
