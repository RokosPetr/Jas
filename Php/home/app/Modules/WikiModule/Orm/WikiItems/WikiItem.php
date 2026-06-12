<?php
declare(strict_types=1);

namespace App\Modules\WikiModule\Orm\WikiItems;

use App\Core\Orm\BaseEntity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                       $id             {primary}
 * @property string                    $name
 * @property int                       $module         {enum self::MODULE_*}
 * @property bool                      $creatable      {default false}
 * @property bool                      $updatable      {default false}
 * @property bool                      $deletable      {default false}
 * @property bool                      $lockable       {default false}
 * @property string                    $remark
 *
 * @property OneHasMany|WikiParam[]    $params         {1:m WikiParam::$item}
 *
 * @property-read bool                 $hasParams      {virtual}
 * @property-read array                $attributes     {virtual}
 */
class WikiItem extends BaseEntity
{
    public const MODULE_SYSTEM = 1;
    public const MODULE_STOCK = 2;
    public const MODULE_DELIVERY = 3;
    public const MODULE_CLI = 4;
    public const MODULE_TRANSPORT = 5;
    public const MODULE_BATHROOM = 6;
    public const MODULE_MTZ = 7;
    public const MODULE_WIKI = 8;

    public const MODULES_LABELS = [
        self::MODULE_SYSTEM => 'System',
        self::MODULE_STOCK => 'Stock',
        self::MODULE_DELIVERY => 'Delivery',
        self::MODULE_CLI => 'Cli',
        self::MODULE_TRANSPORT => 'Transport',
        self::MODULE_BATHROOM => 'Bathroom',
        self::MODULE_MTZ => 'MTZ',
        self::MODULE_WIKI => 'Wiki'
    ];

    public function getterHasParams(): bool
    {
        return $this->params->countStored() > 0;
    }

    public function getterAttributes(): array
    {
        $attribs = [];
        $attribParams = ['creatable', 'updatable', 'deletable', 'lockable'];
        foreach ($attribParams as $attribParam) {
            if ($this->$attribParam) {
                $attribs[] = ucfirst($attribParam);
            }
        }
        return $attribs;
    }
}