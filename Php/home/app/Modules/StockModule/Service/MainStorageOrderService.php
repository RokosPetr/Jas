<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Service;

use App\Modules\StockModule\Orm\StockItems\StockItem;
use App\Service\OrmModel;
use Nextras\Orm\Collection\ICollection;

class MainStorageOrderService
{
    private OrmModel $orm;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
    }

    /**
     * Metoda vraci pocet palet / mnozstvi polozek sortimentu daneho vyrobce, ktere se ma objednat
     * na zaklade prumerne mesicni spotreby ve zvolenem obdobi s filtraci prodejnich extremu
     *
     * @param int $producerId
     * @param float $minStockIndex Nasobek prumerne mesicni spotreby, kdy neni treba objednavat dalsi palety,
     *                             pokud je dane mnozstvi na sklade
     * @param int $monthOrderSize Nasobek prumerne mesicni spotreby, kdery se ma objednavat
     * @param \DateTimeInterface $seasonFrom Zacatek obdobi, ze ktereho se ma pocitat prumerna mesicni spotreba
     * @param \DateTimeInterface $seasonTo Konec obdobi, ze ktereho se ma pocitat prumerna mesicni spotreba
     * @param array $skippedItems Polozky, ktere se nemaji do objednavky zadavat
     * @return array
     */
    public function loadItemsToOrder(
        int $producerId,
        float $minStockIndex,
        int $monthOrderSize,
        \DateTimeInterface $seasonFrom,
        \DateTimeInterface $seasonTo,
        array $skippedItems
    ): array {

        $paletteOrderAmount = [];
        $monthDiff = $this->getMonthDiff($seasonFrom, $seasonTo);
        $filter = ['producer->id' => $producerId, 'status' => StockItem::STATUS_PALETTE];

        if ($skippedItems) {
            $filter['id!='] = $skippedItems;
        }

        foreach ($this->orm->stockItems->findBy($filter) as $stockItem) {
            $salesAverage = $this->getSalesAverage(
                $this->orm->stockItems->loadSales($stockItem->id, $seasonFrom, $seasonTo),
                $this->orm->stockItems->loadCancels($stockItem->id, $seasonFrom, $seasonTo),
                $monthDiff
            );

            if (!$salesAverage) {
                // Pokud se nic neprodalo, neni treba objednavat
                continue;
            }

            if ($minStockIndex * $salesAverage < ($stockItem->mainStorageQuantity + $stockItem->mainStorageOrder)) {
                // Pokud je urcite min mnozstvi na sklade nebo uz je objednane, tak se neobjednava
                continue;
            }

            $orderQuantity = $salesAverage * $monthOrderSize;
            $itemData = [
                'palette' => null,
                'quantity' => round($orderQuantity),
                'salesAverage' => $salesAverage,
                'minOrder' => $stockItem->minOrder ?? 0
            ];

            if ($stockItem->palette) {
                $palettes = ceil($orderQuantity / $stockItem->palette);
                $itemData['palette'] = $palettes;
                $itemData['quantity'] = $palettes * $stockItem->palette;
            }

            if ($itemData['minOrder'] > $itemData['quantity']) {
                $itemData['quantity'] = $itemData['minOrder'];
            }

            $paletteOrderAmount[$stockItem->id] = $itemData;
        }

        return $paletteOrderAmount;
    }

    public function loadSelectedItemsToOrder(ICollection $stockItems): array
    {
        $paletteOrderAmount = [];
        /** @var StockItem $stockItem */
        foreach ($stockItems as $stockItem) {
            $itemData = [
                'palette' => empty($stockItem->palette) ? null : 1,
                'quantity' => empty($stockItem->palette) ? 1 : $stockItem->palette,
                'minOrder' => $stockItem->minOrder ?? 0
            ];

            if ($itemData['minOrder'] > $itemData['quantity']) {
                $itemData['quantity'] = $itemData['minOrder'];
            }

            $paletteOrderAmount[$stockItem->id] = $itemData;
        }

        return $paletteOrderAmount;
    }

    /**
     * Vraci prumerny mesicni prodej s filtraci extremu (3x prumer)
     *
     * @param float[] $sales
     * @param float[] $cancels
     * @param int $monthDiff
     * @return float
     */
    public function getSalesAverage(array $sales, array $cancels, int $monthDiff): float
    {
        $salesAverage = (array_sum($sales) - array_sum($cancels)) / $monthDiff;

        if ($salesAverage <= 0) {
            return 0;
        }

        $filterFunction = static fn(float $value): bool => $value < (3 * $salesAverage);

        return (array_sum(array_filter($sales, $filterFunction)) - array_sum(array_filter($cancels, $filterFunction))) / $monthDiff;
    }

    /** Vraci pocet celych mesicu mezi zadanym intervalem */
    public function getMonthDiff(\DateTimeInterface $seasonFrom, \DateTimeInterface $seasonTo): int
    {
        $monthDiff = ((int) $seasonTo->format('Y') - (int) $seasonFrom->format('Y')) * 12;
        $monthDiff += (int) $seasonTo->format('n') - (int) $seasonFrom->format('n') + 1;
        return $monthDiff;
    }
}
