<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Stands;

use App\Core\Orm\BaseMapper;
use Nextras\Dbal\QueryBuilder\QueryBuilder;

class StandNoteMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_stand_notes';

    public function findByLikeForDatagrid(array $filter, array $order = []): QueryBuilder
    {
        if (isset($filter['state']) && $filter['state'] == StandNote::STATE_ACTIVE) {
            $filter['state'] = [StandNote::STATE_PREPARED, StandNote::STATE_DELIVERED];
        }
        return parent::findByLikeForDatagrid($filter, $order);
    }
}
