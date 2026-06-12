<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Parameters;

use App\Core\Orm\BaseRepository;

class BathParameterRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [BathParameter::class];
    }

    public function changeOrder(BathParameter $parameter, int $newOrder): void
    {
        $oldOrder = $parameter->order;
        $parameterOrders = [$newOrder => $parameter];
        $parameter->order = 0;
        $this->persist($parameter);

        if ($oldOrder > $newOrder) {
            for ($i = $newOrder; $i < $oldOrder; $i++) {
                $tempParameter = $this->getBy(['order' => $i]);
                if (!$tempParameter) {
                    break;
                }
                $tempParameter->order = 0;
                $this->persist($tempParameter);
                $parameterOrders[$i + 1] = $tempParameter;
            }
        }

        if ($oldOrder < $newOrder) {
            for ($i = $newOrder; $i > $oldOrder; $i--) {
                $tempParameter = $this->getBy(['order' => $i]);
                if (!$tempParameter) {
                    break;
                }
                $tempParameter->order = 0;
                $this->persist($tempParameter);
                $parameterOrders[$i - 1] = $tempParameter;
            }
        }

        foreach ($parameterOrders as $order => $tempParameter) {
            $tempParameter->order = $order;
            $this->persist($tempParameter);
        }
    }
}