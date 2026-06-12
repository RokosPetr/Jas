<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Cubicles;

use App\Core\Orm\BaseMapper;
use Nextras\Dbal\QueryBuilder\QueryBuilder;

class CubicleMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_cubicles';

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
