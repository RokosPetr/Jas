<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Transports;

use App\Core\Orm\BaseMapper;
use App\Core\Utils\DateTime;
use Nextras\Dbal\QueryBuilder\QueryBuilder;

class StoreTransportItemMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'trans_store_transport_items';

    public function findByLikeForDatagrid(array $filter, array $order = []): QueryBuilder
    {
        if (!empty($filter['transportDate'])) {
            $filter['target->transport->date='] = \DateTime::createFromFormat(
                DateTime::CZ_DATE,
                $filter['transportDate']
            )->format(DateTime::DB_DATE);
        }
        unset($filter['transportDate']);
        return parent::findByLikeForDatagrid($filter, $order);
    }
}
