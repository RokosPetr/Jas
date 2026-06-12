<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\CustomGroups;

use App\Core\Orm\BaseMapper;

class CustomGroupMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_custom_groups';
}
