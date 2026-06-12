<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\CatalogNumbers;

use App\Core\Orm\BaseMapper;

class CatalogNumberMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'stock_catalog_numbers';

    public function loadCatalogNumbers(): array
    {
        $sql = '
            SELECT CONCAT(i.reg_number, "' . self::DATA_STRING_SEPARATOR . '", n.name) AS dataId, n.id
            FROM `stock_catalog_numbers` AS n
            LEFT JOIN `stock_items` AS i ON i.id = n.item
        ';

        return $this->getConnection()->query($sql)->fetchPairs('dataId', 'id');
    }
}
