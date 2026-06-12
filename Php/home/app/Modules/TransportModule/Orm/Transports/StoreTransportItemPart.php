<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Transports;

use App\Core\Orm\BaseEntity;

/**
 * @property int                                $id             {primary}
 * @property StoreTransportItem                 $item           {m:1 StoreTransportItem::$parts}
 * @property int                                $type           {enum self::TYPE_*}
 */
class StoreTransportItemPart extends BaseEntity
{
    public const TYPE_SELF_PART = 1;
    public const TYPE_CUSTOMER_PART = 2;
    public const TYPE_CANCELLED_PART = 3;

    public const TYPES_LABELS = [
        '' => 'Jiný termín',
        self::TYPE_SELF_PART => 'Tato přeprava',
        self::TYPE_CUSTOMER_PART => 'Vyvezeno zákazníkem',
        self:: TYPE_CANCELLED_PART => 'Stornováno'
    ];
}
