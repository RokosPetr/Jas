<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\CustomerComplaints;

use App\Core\Orm\BaseConventions;
use App\Core\Orm\BaseMapper;
use Nextras\Dbal\QueryBuilder\QueryBuilder;

class CustomerComplaintMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_customer_complaints';

    /** JSON column definition */
    protected function createConventions() : BaseConventions
    {
        $conventions = parent::createConventions();
        $conventions->addMapping(
            'description',
            'description',
            static fn($val) =>  json_decode($val ?? '[]', true),
            static fn($val) =>  json_encode($val ?? [])
        );

        return $conventions;
    }

    public function findByLikeForDatagrid(array $filter, array $order = []): QueryBuilder
    {
        if (isset($filter['state']) && $filter['state'] == 12) {
            $filter['state'] = [CustomerComplaint::STATE_NEW, CustomerComplaint::STATE_NOTIFIED];
        }
        return parent::findByLikeForDatagrid($filter, $order);
    }
}
