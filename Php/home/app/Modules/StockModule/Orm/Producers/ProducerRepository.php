<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Producers;

use App\Core\Orm\BaseRepository;
use App\Modules\StockModule\Orm\StockSeries\StockSeries;

class ProducerRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Producer::class];
    }

    public function loadProducerFilterOption(): array
    {
        $producers = $this->findAll()->orderBy('number')->fetchPairs('id', 'name');
        return $producers + StockSeries::GLOBAL_SERIES_LABELS;
    }
}
