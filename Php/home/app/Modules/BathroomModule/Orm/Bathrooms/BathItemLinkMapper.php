<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Bathrooms;

use App\Core\Orm\BaseMapper;

class BathItemLinkMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'bath_bathroom_item_link';
}