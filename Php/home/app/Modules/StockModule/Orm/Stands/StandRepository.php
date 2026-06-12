<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Stands;

use App\Core\Orm\BaseRepository;

class StandRepository extends BaseRepository
{
    public const IMAGE_DIR = '/www/upload/stands';

    static function getEntityClassNames(): array
    {
        return [Stand::class];
    }

    public function createClone(Stand $stand): Stand
    {
        $newStand = new Stand();
        $newStand->codeFirstPart = $stand->codeFirstPart;
        $newStand->codeSecondPart = $this->getNewCodeSecondPart($stand);
        $newStand->code = "$newStand->codeFirstPart/$newStand->codeSecondPart";
        $newStand->name = $stand->name . ' - kopie';
        $newStand->year = $stand->year;
        $newStand->producer = $stand->producer;
        $newStand->type = $stand->type;
        $newStand->plateOrderType = $stand->plateOrderType;
        $newStand->width = $stand->width;
        $newStand->height = $stand->height;
        $newStand->depth = $stand->depth;
        $newStand->unitCount = $stand->unitCount;
        $this->persist($newStand);

        if ($stand->picture) {
            $newStand->picture = $stand->picture->getRepository()->cloneFile($stand->picture, self::IMAGE_DIR . "/$newStand->id");
        }

        if ($stand->secondPicture) {
            $newStand->secondPicture = $stand->secondPicture->getRepository()
                ->cloneFile($stand->secondPicture, self::IMAGE_DIR . "/$newStand->id");
        }

        foreach ($stand->plates->toCollection()->findBy(['deleted' => false]) as $plate) {
            $newPlate = new StandPlate();
            $newPlate->stand = $newStand;
            $newPlate->order = $plate->order;
            $newPlate->description = $plate->description;
            $newPlate->dimension = $plate->dimension;
            $plate->getRepository()->persist($newPlate);

            if ($plate->picture) {
                $newPlate->picture = $plate->picture->getRepository()
                    ->cloneFile($plate->picture, self::IMAGE_DIR  .  "/$newStand->id/plates/$newPlate->id");
            }

            foreach ($plate->items->toCollection()->findBy(['deleted' => false]) as $item) {
                $newItem = new StandPlateItem();
                $newItem->item = $item->item;
                $newItem->order = $item->order;
                $newItem->photoItem = $item->photoItem;
                $newPlate->items->add($newItem);
            }
        }

        return $this->persistAndFlush($newStand);
    }

    private function getNewCodeSecondPart(Stand $stand): int
    {
        $newNumber = $stand->codeSecondPart + 1;
        while ($this->getBy(['codeFirstPart' => $stand->codeFirstPart, 'codeSecondPart' => $newNumber])) {
            $newNumber++;
        }
        return $newNumber;
    }
}
