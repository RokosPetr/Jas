<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockGroups;

use App\Core\Orm\BaseEntity;
use App\Modules\StockModule\Orm\CustomGroups\CustomGroup;
use App\Modules\StockModule\Orm\Producers\Producer;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * @property int                          $id             {primary}
 * @property Producer                     $producer       {m:1 Producer::$stockGroups}
 * @property int                          $number
 * @property string                       $name
 * @property bool                         $noTransfers    {default false}
 * @property ManyHasMany|CustomGroup[]    $customGroups   {m:m CustomGroup::$stockGroups}
 *
 * @property-read string                  $title          {virtual}
 * @property-read string                  $groupId        {virtual}
 */
class StockGroup extends BaseEntity
{
    public const TILES_OUTLET = 20;
    public const SANITARY_OUTLET = [45, 50];
    public const INACTIVE_ITEMS = [18, 19, 20, 21];

    public function getterTitle(): string
    {
        return "$this->number - $this->name";
    }

    public function getterGroupId(): string
    {
        return $this->producer->number . '-' . $this->number;
    }
}
