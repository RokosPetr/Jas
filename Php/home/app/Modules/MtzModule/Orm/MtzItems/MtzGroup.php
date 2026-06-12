<?php
declare(strict_types=1);

namespace App\Modules\MtzModule\Orm\MtzItems;

use App\Core\Orm\BaseEntity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                            $id               {primary}
 * @property int                            $parent
 * @property string                         $name
 * @property int                            $order
 * @property int                            $tax
 * @property OneHasMany|MtzItem[]           $items            {1:m MtzItem::$group}
 *
 * @property-read bool                      $hasItems         {virtual}
 * @property-read string                    $title            {virtual}
 */
class MtzGroup extends BaseEntity
{
    public const BASE_TAX = 21;

    public function getterHasItems(): bool
    {
        return $this->items->countStored() > 0;
    }

    public function getterTitle(): string
    {
        return "$this->order - $this->name";
    }
}
