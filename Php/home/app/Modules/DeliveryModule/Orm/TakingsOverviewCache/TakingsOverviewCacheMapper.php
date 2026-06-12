<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\TakingsOverviewCache;

use App\Core\Orm\BaseMapper;

class TakingsOverviewCacheMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'cache_takings_overview';

    public function updateCacheTakingsOverview2025(int $year, int $month): void
    {
        $this->getConnection()->query('CALL update_cache_takings_overview_2025('. $year . ', '. $month . ')');
    }
}
