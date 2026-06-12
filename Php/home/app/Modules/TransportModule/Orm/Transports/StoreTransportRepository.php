<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Transports;

use App\Core\Orm\BaseRepository;
use App\Core\Utils\DateTime;
use App\Modules\TransportModule\Orm\Cars\StoreCar;
use Nextras\Orm\Collection\ICollection;

class StoreTransportRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StoreTransport::class];
    }

    public function findInvalidTransports(int $store): ICollection
    {
        $invalidTransportIds = $this->getMapper()->findInvalidTransports($store);
        return $this->findBy(['id' => $invalidTransportIds ?: null]);
    }

    public function findDriverDayTransports(int $driverId): ICollection
    {
        return $this->findBy([
            ICollection::AND,
            [
                ICollection::AND,
                'deleted' => false,
                'driver->id' => $driverId,
                'date' => date(DateTime::DB_DATE)
            ],
            [
                ICollection::OR,
                'type' => StoreTransport::TYPE_UNAVAILABILITY,
                'targets->id!=' => null
            ]
        ])->orderBy('timeFrom');
    }

    public function findCarDayTransports(StoreCar $car, \DateTimeInterface $date): ICollection
    {
        return $this->findBy([
            ICollection::AND,
            [
                ICollection::AND,
                'deleted' => false,
                'car->id' => $car->id,
                'date' => $date->format(DateTime::DB_DATE)
            ],
            [
                ICollection::OR,
                'type' => StoreTransport::TYPE_UNAVAILABILITY,
                'targets->id!=' => null
            ]
        ])->orderBy('timeFrom');
    }
}
