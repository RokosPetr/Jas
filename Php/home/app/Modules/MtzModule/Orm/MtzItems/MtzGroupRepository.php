<?php
declare(strict_types=1);

namespace App\Modules\MtzModule\Orm\MtzItems;

use App\Core\Orm\BaseRepository;

class MtzGroupRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [MtzGroup::class];
    }

    public function changeOrder(MtzGroup $mtzGroup, int $newOrder): void
    {
        $oldOrder = $mtzGroup->order;
        $mtzGroupOrders = [$newOrder => $mtzGroup];
        $mtzGroup->order = 0;
        $this->persist($mtzGroup);

        if ($oldOrder > $newOrder) {
            for ($i = $newOrder; $i < $oldOrder; $i++) {
                $tempMtzGroup = $this->getBy(['order' => $i]);
                if (!$tempMtzGroup) {
                    break;
                }
                $tempMtzGroup->order = 0;
                $this->persist($tempMtzGroup);
                $mtzGroupOrders[$i + 1] = $tempMtzGroup;
            }
        }

        if ($oldOrder < $newOrder) {
            for ($i = $newOrder; $i > $oldOrder; $i--) {
                $tempMtzGroup = $this->getBy(['order' => $i]);
                if (!$tempMtzGroup) {
                    break;
                }
                $tempMtzGroup->order = 0;
                $this->persist($tempMtzGroup);
                $mtzGroupOrders[$i - 1] = $tempMtzGroup;
            }
        }

        foreach ($mtzGroupOrders as $order => $tempMtzGroup) {
            $tempMtzGroup->order = $order;
            $this->persist($tempMtzGroup);
        }
    }
}
