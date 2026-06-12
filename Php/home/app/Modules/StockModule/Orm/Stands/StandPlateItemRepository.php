<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Stands;

use App\Core\Orm\BaseRepository;

class StandPlateItemRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StandPlateItem::class];
    }

    public function changeOrder(StandPlateItem $item, int $newOrder): void
    {
        $oldOrder = $item->order;
        $itemOrders = [$newOrder => $item];
        $item->order = -$item->order;
        $this->persist($item);

        if ($oldOrder > $newOrder) {
            for ($i = $newOrder; $i < $oldOrder; $i++) {
                $tempItem = $this->getBy(['plate->id' => $item->plate->id, 'order' => $i, 'deleted' => false]);
                if (!$tempItem) {
                    break;
                }
                $tempItem->order = -$tempItem->order;
                $this->persist($tempItem);
                $itemOrders[$i + 1] = $tempItem;
            }
        }

        if ($oldOrder < $newOrder) {
            for ($i = $newOrder; $i > $oldOrder; $i--) {
                $tempItem = $this->getBy(['plate->id' => $item->plate->id, 'order' => $i, 'deleted' => false]);
                if (!$tempItem) {
                    break;
                }
                $tempItem->order = -$tempItem->order;
                $this->persist($tempItem);
                $itemOrders[$i - 1] = $tempItem;
            }
        }

        foreach ($itemOrders as $order => $tempItem) {
            $tempItem->order = $order;
            $this->persist($tempItem);
        }
    }

    public function createClone(StandPlateItem $plateItem): StandPlateItem
    {
        $copy = new StandPlateItem();
        $copy->plate = $plateItem->plate;
        $copy->item = $plateItem->item;
        $copy->order = $plateItem->order;
        $copy->photoItem = $plateItem->photoItem;
        $this->persist($copy);

        if ($plateItem->picture) {
            $stand = $plateItem->plate->stand;
            $plate = $plateItem->plate;
            $imageSubDir = $stand->hasPlates
                ? "/$stand->id/plates/$plate->id/items/$copy->id"
                : "/$stand->id/items/$copy->id";
            $copy->picture = $plateItem->picture->getRepository()->cloneFile(
                $plateItem->picture,
                StandRepository::IMAGE_DIR . $imageSubDir
            );
            $this->persist($copy);
        }

        $plateItem->deleted = true;
        $plateItem->deletedBy = $this->getModel()->getSysUser();
        $plateItem->deletedAt = new \DateTimeImmutable();
        $this->persist($plateItem);
        return $copy;
    }
}
