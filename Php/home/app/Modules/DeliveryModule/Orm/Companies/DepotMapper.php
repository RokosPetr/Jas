<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Companies;

use App\Core\Orm\BaseConventions;
use App\Core\Orm\BaseMapper;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nextras\Dbal\QueryBuilder\QueryBuilder;

class DepotMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_company_depots';

    /** DB vazebni tabulka */
    public string $table_delivery_company_depots_sys_users = 'delivery_company_depots_dealers';

    /** JSON column definition */
    protected function createConventions() : BaseConventions
    {
        $conventions = parent::createConventions();
        $conventions->addMapping(
            'information',
            'information',
            static fn($val) =>  json_decode($val ?? '[]', true),
            static fn($val) =>  json_encode($val ?? [])
        );

        return $conventions;
    }

    public function findByLikeForDatagrid(array $filter, array $order = []): QueryBuilder
    {
        if (!empty($filter['store']) && in_array(Store::MAIN_STORAGE, $filter['store'])) {
            $storeFilter = $filter['store'];
            unset($storeFilter[array_search(Store::MAIN_STORAGE, $storeFilter)]);
            $filter['store'] = array_merge($storeFilter, Store::MAIN_STORAGES);
        }

        return parent::findByLikeForDatagrid($filter, $order);
    }

    public function loadStoreDepots(int $storeId): array
    {
        $sql = '
            SELECT CONCAT(c.ico, "' . self::DATA_STRING_SEPARATOR . '", d.voj) as dataId, d.id
            FROM `delivery_company_depots` AS d
            LEFT JOIN `delivery_companies` AS c ON c.id = d.company
            WHERE d.store = %i
        ';

        return $this->getConnection()->query($sql, $storeId)->fetchPairs('dataId', 'id');
    }

    public function loadStorageDepots(): array
    {
        $sql = '
            SELECT CONCAT(s.id, "' . self::DATA_STRING_SEPARATOR . '", c.ico, "' . self::DATA_STRING_SEPARATOR . '", d.voj) as dataId, d.id
            FROM `delivery_company_depots` AS d
            JOIN `delivery_companies` AS c ON c.id = d.company
            JOIN `sys_stores` AS s ON s.id = d.store
            LEFT JOIN `delivery_company_groups` AS g ON g.id = d.group
            WHERE c.ico NOT IN %i[]
            AND g.number > 0
        ';

        return $this->getConnection()->query($sql, [0, Store::INTERNAL_ICO])->fetchPairs('dataId', 'id');
    }
}
