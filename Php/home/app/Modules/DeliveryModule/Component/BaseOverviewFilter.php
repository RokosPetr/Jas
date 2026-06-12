<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Component;

use App\Modules\DeliveryModule\Component\Entity\SalesFilterEntity;
use App\Service\OrmModel;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Http\SessionSection;
use Nextras\Orm\Collection\Expression\LikeExpression;
use Nextras\Orm\Collection\ICollection;

abstract class BaseOverviewFilter extends Control
{
    protected string $overviewName;
    protected OrmModel $orm;
    protected SessionSection $session;

    public int $show;
    public array $years;
    public int $month;
    public int $stockGroup;
    public array $producers;
    public int $company;
    public int $stockSeries;
    public int $stockItem;

    public const TABLE_OVERVIEW = 1;
    public const GRID_OVERVIEW = 2;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
        $this->monitor(Presenter::class, function (): void {
            $this->session = $this->getPresenter()->getSession($this->overviewName);
        });
    }

    abstract protected function getCompanyFilter(): array;
    abstract public function getDataFilter(): SalesFilterEntity;

    public function loadState(array $params): void
    {
        $this->show = $this->getPresenter()->getAction() === 'overview' ? self::TABLE_OVERVIEW : self::GRID_OVERVIEW;
        $this->years = $this->session->years ?? [date('Y'), (string) (date('Y') - 1)];
        $this->month = $this->session->month ?? 0;
        $this->stockGroup = $this->session->stockGroup ?? 0;
        $this->company = $this->session->company ?? 0;
        $this->stockSeries = $this->session->stockSeries ?? 0;
        $this->stockItem = $this->session->stockItem ?? 0;

        if (!isset($this->session->groupProducers[$this->stockGroup])) {
            $this->session->groupProducers[$this->stockGroup] = array_keys($this->loadProducers());
        }

        $this->producers = $this->session->groupProducers[$this->stockGroup];
    }

    public function handleSetMultiselectValues(): void
    {
        $param = $this->getPresenter()->getParameter('name');
        $values = $this->getPresenter()->getParameter('values') ?? [];
        $this->$param = $this->session->$param = $values;
        $this->redrawControls();
    }

    public function handleSetValue(): void
    {
        $param = $this->getPresenter()->getParameter('name');
        $value = (int) $this->getPresenter()->getParameter('value');

        if ($param === 'show') {
            $this->getPresenter()->redirect($value === self::TABLE_OVERVIEW ? 'overview' : 'overviewGrid');
        }

        $this->$param = $this->session->$param = $value;

        if ($param === 'stockGroup') {
            if (!isset($this->session->groupProducers[$this->stockGroup])) {
                $this->session->groupProducers[$this->stockGroup] = array_keys($this->loadProducers());
            }
            $this->producers = $this->session->groupProducers[$this->stockGroup];
            $this->stockSeries = $this->session->stockSeries = 0;
            $this->stockItem = $this->session->stockItem = 0;
        }

        if ($param === 'stockSeries') {
            $this->stockItem = $this->session->stockItem = 0;
        }

        $this->redrawControls();
    }

    public function handleSetProducers(): void
    {
        $values = $this->getPresenter()->getParameter('values') ?? [];
        if (in_array(0, $values)) {
            // vybrat vse
            $values = array_keys($this->loadProducers());
        }
        $this->producers = $this->session->groupProducers[$this->stockGroup] = $values;
        $this->stockSeries = $this->session->stockSeries = 0;
        $this->stockItem = $this->session->stockItem = 0;
        $this->redrawControls();
    }

    public function handleUpdateCompanySelect(): void
    {
        $result = [['id' => 0, 'text' => 'Vše']];
        $search = trim($this->getPresenter()->getParameter('search'));

        if (!$search) {
            $this->getPresenter()->sendJson(['results' => $result]);
        }

        $filter = $this->getCompanyFilter();

        if (preg_match("/^\d+$/", $search)) {
            $filter['ico~'] = LikeExpression::contains(ltrim($search, '0'));
        } else {
            $filter['name~'] = LikeExpression::contains($search);
        }

        $companyCollection = $this->orm->companies->findBy($filter)->orderBy('ico');

        if ($companyCollection->countStored() > 100) {
            $this->getPresenter()->sendJson(['results' => $result]);
        }

        foreach ($companyCollection as $company) {
            $result[] = [
                'id' => $company->id,
                'text' => "$company->icoString - $company->name"
            ];
        }

        $this->getPresenter()->sendJson(['results' => $result]);
    }

    public function handleCancelCompany(): void
    {
        $this->resetCompany();
        $this->redrawControls();
    }

    public function render(): void
    {
        $this->template->stockGroups = $this->orm->customGroups->findAll()->fetchPairs('id', 'name');
        $this->template->producers = $this->loadProducers();
        $this->template->company = $this->company ? $this->orm->companies->getById($this->company) : null;
        $this->template->seriesSets = $this->loadSeries();
        $this->template->seriesItems = $this->loadItems();

        $this->template->setFile(__DIR__ . '/templates/' . $this->overviewName . 'Filter.latte');
        $this->template->render();
    }

    public function resetCompany(): void
    {
        $this->company = $this->session->company = 0;
    }

    protected function redrawControls(): void
    {
        $this->getParent()->redrawControl('overviewFilter');
        $this->getParent()->redrawControl('overviewTable');
    }

    private function loadProducers(): array
    {
        $stockGroup = $this->orm->customGroups->getById($this->stockGroup);
        return $stockGroup ? $stockGroup->loadProducers(true) : [];
    }

    private function loadSeries(): array
    {
        if (count($this->producers) !== 1) {
            return [];
        }
        return $this->orm->stockSeries->findBy([
            ICollection::OR,
            'items->producer->id' => $this->producers[0],
            'items->producer->children->id' => $this->producers[0]
        ])
            ->orderBy('name')
            ->fetchPairs('id', 'name');
    }

    private function loadItems(): array
    {
        if (!$this->stockSeries) {
            return [];
        }
        $itemSelect = [];
        $stockItems = $this->orm->stockItems->findBy([
            'series->id' => $this->stockSeries,
            'group->customGroups->id' => $this->stockGroup
        ])->orderBy('name')->fetchAll();

        foreach ($stockItems as $stockItem) {
            $itemSelect[$stockItem->id] = $stockItem->regNumber
                . '#'
                . $stockItem->storageCatalog
                . '#'
                . $stockItem->name;
        }

        return $itemSelect;
    }
}