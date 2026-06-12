<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Cars;

use App\Core\Orm\BaseMapper;

class StoreCarMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'trans_store_cars';

    /** DB vazebni tabulka */
    public string $table_trans_store_cars_sys_stores = 'trans_cars_stores';
}
