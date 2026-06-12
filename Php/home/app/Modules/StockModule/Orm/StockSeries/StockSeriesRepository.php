<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockSeries;

use App\Core\Orm\BaseRepository;

/**
 * @method array loadStockSeries()
 */
class StockSeriesRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StockSeries::class];
    }
}
