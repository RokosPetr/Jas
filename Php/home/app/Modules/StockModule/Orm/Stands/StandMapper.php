<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Stands;

use App\Core\Orm\BaseMapper;
use Nextras\Dbal\QueryBuilder\QueryBuilder;

class StandMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_stands';

    public function findByLikeForDatagrid(array $filter, array $order = []): QueryBuilder
    {
        if (isset($order['code'])) {
            $order['codeFirstPart'] = $order['code'];
            $order['codeSecondPart'] = $order['code'];
            unset($order['code']);
        }
        return parent::findByLikeForDatagrid($filter, $order);
    }
}
