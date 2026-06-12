<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Parameters;

use App\Core\Orm\BaseEntity;
use App\Modules\SystemModule\Orm\Files\File;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                       $id             {primary}
 * @property string                    $name
 * @property int                       $order
 * @property OneHasMany|BathOption[]   $options        {1:m BathOption::$parameter}
 *
 * @property-read bool                 $hasOptions     {virtual}
 */
class BathParameter extends BaseEntity
{
    public const TYPE = 1;
    public const COLOR = 2;
    public const SERIES = 6;

    public function getterHasOptions(): bool
    {
        return $this->options->count() > 0;
    }
}