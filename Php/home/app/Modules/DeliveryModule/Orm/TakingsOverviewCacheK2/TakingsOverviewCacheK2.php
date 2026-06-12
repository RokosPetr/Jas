<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\TakingsOverviewCacheK2;

use App\Core\Orm\BaseEntity;

/**
 * @property int                            $id               {primary}
 * @property int                            $type             {enum self::TYPE_*} {default self::TYPE_STORE_TAKINGS}
 * @property int                            $store
 * @property int                            $group
 * @property int                            $producer
 * @property int                            $year
 * @property int                            $month
 * @property int                            $value
 * @property int                            $k2_partner_id
 * @property string                         $k2_partner_code
 */
class TakingsOverviewCacheK2 extends BaseEntity
{
    public const TYPE_STORE_TAKINGS = 1;
    public const TYPE_STORE_SELLS = 2;
}
