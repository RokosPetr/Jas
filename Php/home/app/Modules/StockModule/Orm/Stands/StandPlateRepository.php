<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Stands;

use App\Core\Orm\BaseRepository;

class StandPlateRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StandPlate::class];
    }

    public function changeOrder(StandPlate $plate, int $newOrder): void
    {
        $oldOrder = $plate->order;
        $plateOrders = [$newOrder => $plate];
        $plate->order = -$plate->order;
        $this->persist($plate);

        if ($oldOrder > $newOrder) {
            for ($i = $newOrder; $i < $oldOrder; $i++) {
                $tempPlate = $this->getBy(['stand->id' => $plate->stand->id, 'order' => $i, 'deleted' => false]);
                if (!$tempPlate) {
                    break;
                }
                $tempPlate->order = -$tempPlate->order;
                $this->persist($tempPlate);
                $plateOrders[$i + 1] = $tempPlate;
            }
        }

        if ($oldOrder < $newOrder) {
            for ($i = $newOrder; $i > $oldOrder; $i--) {
                $tempPlate = $this->getBy(['stand->id' => $plate->stand->id, 'order' => $i, 'deleted' => false]);
                if (!$tempPlate) {
                    break;
                }
                $tempPlate->order = -$tempPlate->order;
                $this->persist($tempPlate);
                $plateOrders[$i - 1] = $tempPlate;
            }
        }

        foreach ($plateOrders as $order => $tempPlate) {
            $tempPlate->order = $order;
            $this->persist($tempPlate);
        }
    }
}
