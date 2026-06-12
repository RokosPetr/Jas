<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Presenters;

use App\Modules\Presenters\SecurePresenter;
use Nextras\Orm\Collection\DbalCollection;
use Nextras\Orm\Collection\Expression\LikeExpression;

/** Presenter pro vyhledavani polozek sortimentu */
final class StockItemPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Skladové zásoby',
        'preview' => 'Náhled skladové položky',
        'variants' => 'Množství variant'
    ];

    /** Vyhledavani polozky sortimentu */
    public function actionDefault(): void
    {
        $search = $this->getSession($this->getName())->searchItemTerm ?? '';
        $this->template->search = $search;
        $this->template->items = $this->getFilteredItems($search);
        $this->template->showAll = false;

    }

    /** AJAX odpoved na zadany text pro vyhledani polozky sortimentu */
    public function handleUpdateSearchItems(bool $showAll = false): void
    {
        $search = trim($this->getParameter('search'));
        $this->getSession($this->getName())->searchItemTerm = $search;
        $this->template->items = $this->getFilteredItems($search);
        $this->template->showAll = $showAll;
        $this->redrawControl('searched-items');
    }

    /** Nahled polozky sortimentu - maximalni pocty jedne varianty dane polozky sortimentu na vsech pobockach */
    public function actionPreview(int $id): void
    {
        $stockItem = $this->orm->stockItems->getById($id);
        if (!$stockItem) {
            $this->error('Položka nenalezena');
        }
        $this->template->stockItem = $stockItem;
        $this->template->stores = $this->orm->stores->findOrderedStoreNames($stockItem->producer->number ?? 0);
        $this->template->storeQuantites = $stockItem->loadStoreMaxQuantities();
        $this->sideDialogAjaxHandler();
    }

    /** Pocty vsech variant zvolene polozky sortimentu na vsech pobockach */
    public function actionVariants(int $id): void
    {
        $stockItem = $this->orm->stockItems->getById($id);
        if (!$stockItem) {
            $this->error('Položka nenalezena');
        }
        $this->template->stores = $this->orm->stores->findOrderedStoreNames($stockItem->producer->number ?? 0);
        $this->template->stockItem = $stockItem;
        $this->template->variants = $this->orm->stockVariants->findByItemPerStore($id);
        $this->template->imports = $this->orm->imports->findBy([
            'name~' => LikeExpression::startsWith('Skladové položky')]
        )->fetchPairs('name');
    }

    private function getFilteredItems(string $search): ?DbalCollection
    {
        if (!$search) {
            return null;
        }
        $filter = [
            'query' => explode(' ', $search),
            'fulltextColumns' => ['regNumber', 'name', 'catalog'],
            'status' => 'all'
        ];
        return $this->orm->stockItems->getDataForDatagrid($filter, []);
    }
}
