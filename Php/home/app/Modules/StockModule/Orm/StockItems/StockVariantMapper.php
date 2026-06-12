<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockItems;

use App\Core\Orm\BaseMapper;
use App\Modules\StockModule\Orm\StockSeries\StockSeries;
use Nextras\Dbal\QueryBuilder\QueryBuilder;
use Nextras\Orm\Collection\ICollection;

class StockVariantMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_variants';

    public function findByLikeForDatagrid(array $filter, array $order = []): QueryBuilder
    {
        if (isset($filter['producerId'])) {
            $producers = [];
            $globals = [];
            $producerFilter = is_array($filter['producerId']) ? $filter['producerId'] : [$filter['producerId']];
            unset($filter['producerId']);

            foreach ($producerFilter as $producerId) {
                if (isset(StockSeries::GLOBAL_SERIES[$producerId])) {
                    $globals[] = StockSeries::GLOBAL_SERIES[$producerId];
                } else {
                    $producers[] = $producerId;
                }
            }

            if ($globals && !$producers) {
                $filter['globalProducerId='] = $globals;
            } elseif (!$globals && $producers) {
                $filter['producerId='] = $producers;
                $filter['globalProducerId='] = null;
            } else {
                $filter['id='] = $this->getRepository()->findBy([
                    ICollection::OR,
                    'item->producer->id' => $producers,
                    'item->globalProducer' => $globals
                ])->fetchPairs(null, 'id');
            }
        }
        return parent::findByLikeForDatagrid($filter, $order);
    }

    public function loadStoreVariants(int $storeId): array
    {
        $sql = '
            SELECT CONCAT(i.reg_number, "' . self::DATA_STRING_SEPARATOR . '", v.supplement) as dataId, v.id
            FROM `stock_variants` AS v
            LEFT JOIN `stock_items` AS i ON i.id = v.item
            WHERE v.store = %i
        ';

        return $this->getConnection()->query($sql, $storeId)->fetchPairs('dataId', 'id');
    }

    public function loadStoreOutlets(int $storeId): array
    {
        $sql = '
            SELECT CONCAT(i.reg_number, "' . self::DATA_STRING_SEPARATOR . '", v.supplement) as dataId, v.outlet_type
            FROM `stock_variants` AS v
            LEFT JOIN `stock_items` AS i ON i.id = v.item
            WHERE v.store = %i AND v.outlet_type IS NOT NULL
        ';

        return $this->getConnection()->query($sql, $storeId)->fetchPairs('dataId', 'outlet_type');
    }
}
