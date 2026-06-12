<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Producers;

use App\Core\Orm\BaseMapper;

class ProducerMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_producers';
}
