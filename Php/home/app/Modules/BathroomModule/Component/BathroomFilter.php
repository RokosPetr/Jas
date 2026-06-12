<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Component;

use App\Modules\BathroomModule\Orm\Parameters\BathParameter;
use App\Service\OrmModel;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Http\SessionSection;
use Nette\Utils\Paginator;
use Nextras\Orm\Collection\Functions\AvgAggregateFunction;
use Nextras\Orm\Collection\ICollection;

class BathroomFilter extends Control
{
    public const ORDER_DEFAULT = 1;
    public const ORDER_POPULARITY = 2;
    protected OrmModel $orm;
    protected SessionSection $session;
    public Paginator $paginator;

    private array $visibleOptions = [];
    public array $selectedOptions = [];
    public int $bathType;
    public int $itemsPerPage;
    public int $page;
    public int $orderType;
    public array $itemsPerPageOption = [9, 18, 27];

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
        $this->paginator = new Paginator();
        $this->monitor(Presenter::class, function (): void {
            $this->session = $this->getPresenter()->getSession('bathroomFilter');
        });
    }

    public function loadState(array $params): void
    {
        $this->selectedOptions = $this->session->selectedOptions ?? [];
        $this->bathType = $this->session->bathType ?? 0;
        $this->orderType = $this->session->orderType ?? self::ORDER_DEFAULT;
        $this->itemsPerPage = $this->session->itemsPerPage ?? 9;
        $this->page = $this->session->page ?? 1;
        $this->paginator->setPage($this->page);
        $this->paginator->setItemsPerPage($this->itemsPerPage);
        parent::loadState($params);
    }

    public function handleSetOrder(int $type): void
    {
        $this->orderType = $this->session->orderType = $type;
        $this->reloadPage();
    }

    public function handleSetItemsPerPage(int $itemsPerPage): void
    {
        $this->itemsPerPage = $this->session->itemsPerPage = $itemsPerPage;
        $this->page = $this->session->page = 1;
        $this->reloadPage();
    }

    public function handlePaginate(int $page): void
    {
        $this->page = $this->session->page = $page;
        $this->reloadPage();
    }

    public function handleSetBathType(): void
    {
        $bathType = (int) $this->getPresenter()->getParameter('bathType');
        $this->bathType = $this->session->bathType = $bathType;
        $this->page = $this->session->page = 1;
        $this->reloadPage();
    }

    public function handleToggleParam(): void
    {
        $param = (int) $this->getPresenter()->getParameter('param');
        $option = (int) $this->getPresenter()->getParameter('option');

        if ($this->isSelected($param, $option)) {
            unset($this->selectedOptions[$param][$option]);
        } else {
            $this->selectedOptions[$param][$option] = true;
        }

        $this->session->selectedOptions = $this->selectedOptions;
        $this->page = $this->session->page = 1;
        $this->reloadPage();
    }

    public function handleCancelParamFilters(): void
    {
        $this->selectedOptions = $this->session->selectedOptions = [];
        $this->page = $this->session->page = 1;
        $this->reloadPage();
    }

    public function render(): void
    {
        $this->template->bathParams = $this->orm->bathParameters->findAll()->orderBy('order')->fetchPairs('id');
        $this->template->bathrooms = $this->loadBathRooms();
        $this->template->showCancelFilter = !!array_filter($this->selectedOptions);
        $this->template->setFile(__DIR__ . '/templates/bathroomFilter.latte');
        $this->template->render();
    }

    public function isSelected(int $param, int $option): bool
    {
        return $this->selectedOptions[$param][$option] ?? false;
    }

    public function isVisible(int $param, int $option): bool
    {
        return isset($this->visibleOptions[$param][$option]) || $this->isSelected($param, $option);
    }

    public function getPaginatorSteps(): array
    {
        $page = $this->paginator->getPage();

        if ($this->paginator->pageCount < 2) {
            return [$page];
        }

        $steps = range(max($this->paginator->firstPage, $page - 2), min($this->paginator->lastPage, $page + 2));
        $count = 2;
        $quotient = ($this->paginator->pageCount - 1) / $count;

        for ($i = 0; $i <= $count; $i++) {
            $steps[] = round($quotient * $i) + $this->paginator->firstPage;
        }

        sort($steps);
        return array_values(array_unique($steps));
    }

    protected function loadBathRooms(): array
    {
        if (!$this->bathType) {
            $this->paginator->setItemCount(0);
            return [];
        }

        $ids = $this->orm->bathrooms->findBy(['options->id' => $this->bathType, 'deleted' => false])
            ->fetchPairs(null, 'id');

        $this->loadVisibleOptions($ids);

        foreach ($this->selectedOptions as $options) {
            if (!empty($options)) {
                $ids = $this->orm->bathrooms->findBy(['id' => $ids, 'options->id' => array_keys($options)])
                    ->fetchPairs(null, 'id');
            }
        }

        $this->paginator->setItemCount(count($ids));
        $bathCollection = $this->orm->bathrooms->findBy(['id' => $ids]);

        if ($this->orderType === self::ORDER_POPULARITY) {
            $bathCollection = $bathCollection->orderBy(
                [AvgAggregateFunction::class, 'ratings->rating'],
                ICollection::DESC
            );
        }

        return $bathCollection
            ->orderBy('priority', ICollection::DESC)
            ->orderBy('id', ICollection::DESC)
            ->limitBy($this->paginator->getLength(), $this->paginator->getOffset())
            ->fetchAll();
    }

    protected function reloadPage(): void
    {
        $this->paginator->setPage($this->page);
        $this->paginator->setItemsPerPage($this->itemsPerPage);
        $this->redrawControl('bathroom-filter');
    }

    private function loadVisibleOptions(array $bathIds): void
    {
        $bathParamIds = $this->orm->bathParameters->findBy(['id!=' => BathParameter::TYPE])->fetchPairs(null, 'id');

        foreach ($bathParamIds as $bathParamId) {
            $ids = $bathIds;

            foreach ($this->selectedOptions as $param => $options) {
                if (!empty($options) && $param !== $bathParamId) {
                    $ids = $this->orm->bathrooms->findBy(['id' => $ids, 'options->id' => array_keys($options)])
                        ->fetchPairs(null, 'id');
                }
            }

            $this->visibleOptions[$bathParamId] = $this->orm->bathOptions->findBy([
                'parameter->id' => $bathParamId,
                'bathrooms->id' => $ids
            ])->fetchPairs('id', 'id');
        }
    }
}