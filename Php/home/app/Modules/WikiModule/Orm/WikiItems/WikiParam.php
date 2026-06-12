<?php
declare(strict_types=1);

namespace App\Modules\WikiModule\Orm\WikiItems;

use App\Core\Orm\BaseEntity;

/**
 * @property int                       $id             {primary}
 * @property WikiItem                  $item           {m:1 WikiItem::$params}
 * @property string                    $name
 * @property int                       $order
 * @property int                       $type           {enum self::TYPE_*}
 * @property bool                      $virtual        {default false}
 * @property string                    $remark
 */
class WikiParam extends BaseEntity
{
    public const TYPE_INTEGER = 1;
    public const TYPE_STRING = 2;
    public const TYPE_ENUM = 3;
    public const TYPE_BOOL = 4;
    public const TYPE_DECIMAL = 5;
    public const TYPE_DATE = 6;
    public const TYPE_ENTITY = 7;
    public const TYPE_COLLECTION = 8;

    public const TYPES_LABELS = [
        self::TYPE_INTEGER => 'Integer',
        self::TYPE_STRING => 'String',
        self::TYPE_ENUM => 'Enum',
        self::TYPE_BOOL => 'Boolean',
        self::TYPE_DECIMAL => 'Float',
        self::TYPE_DATE => 'Datum',
        self::TYPE_ENTITY => 'Entita',
        self::TYPE_COLLECTION => 'Array'
    ];
}