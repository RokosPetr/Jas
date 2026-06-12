<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\MainStorageOrders;

use App\Core\Orm\BaseRepository;
use Nextras\Orm\Collection\ICollection;

/**
 * @method array loadProducersForFilter()
 * @method array loadSeriesForFilter()
 */
class MainStorageOrderRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [MainStorageOrder::class];
    }

    public function createNewOrder(): MainStorageOrder
    {
        $year = (int) date('Y');
        $month = (int) date('n');
        $lastOrderNumber = $this->findBy(['year' => $year, 'month' => $month])
            ->orderBy('number', ICollection::DESC)
            ->fetch()->number ?? 0;
        $order = new MainStorageOrder();
        $order->year = $year;
        $order->month = $month;
        $order->number = ++$lastOrderNumber;
        $this->persist($order);
        return $order;
    }
}
