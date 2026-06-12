<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Parameters;

use App\Core\Orm\BaseRepository;

class BathOptionRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [BathOption::class];
    }

    public const IMAGE_DIR = '/www/upload/bathOptions';

    public function changeOrder(BathOption $option, int $newOrder): void
    {
        $oldOrder = $option->order;
        $optionOrders = [$newOrder => $option];
        $option->order = 0;
        $this->persist($option);

        if ($oldOrder > $newOrder) {
            for ($i = $newOrder; $i < $oldOrder; $i++) {
                $tempOption = $this->getBy(['parameter->id' => $option->parameter->id, 'order' => $i]);
                if (!$tempOption) {
                    break;
                }
                $tempOption->order = 0;
                $this->persist($tempOption);
                $optionOrders[$i + 1] = $tempOption;
            }
        }

        if ($oldOrder < $newOrder) {
            for ($i = $newOrder; $i > $oldOrder; $i--) {
                $tempOption = $this->getBy(['parameter->id' => $option->parameter->id, 'order' => $i]);
                if (!$tempOption) {
                    break;
                }
                $tempOption->order = 0;
                $this->persist($tempOption);
                $optionOrders[$i - 1] = $tempOption;
            }
        }

        foreach ($optionOrders as $order => $tempOption) {
            $tempOption->order = $order;
            $this->persist($tempOption);
        }
    }
}