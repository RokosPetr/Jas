<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Component;

use App\Core\Utils\DateTime;
use App\Modules\TransportModule\Service\StoreTransportService;
use App\Service\OrmModel;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Http\SessionSection;

class StoreCarOccupancy extends Control
{
    private OrmModel $orm;
    private SessionSection $session;

    public int $year;
    public array $cars = [];
    public array $months = [];

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
        $this->monitor(Presenter::class, function (): void {
            $this->session = $this->getPresenter()->getSession('storeCarOccupancy');
        });
    }

    public function loadState(array $params): void
    {
        parent::loadState($params);
        $this->year = $this->session->year ?? (int) date('Y');
        $this->cars = $this->session->cars ?? $this->orm->storeCars->findBy(['deleted' => false])->fetchPairs(null, 'id');
        $this->months = $this->session->months ?? array_keys(DateTime::CZ_MONTHS);
    }

    public function handleSetYear(): void
    {
        $this->year = $this->session->year = (int) $this->getPresenter()->getParameter('year');
        $this->redrawControl('occupancyTable');
    }

    public function handleSetCars(): void
    {
        $cars = $this->getPresenter()->getParameter('cars') ?? [];
        if (in_array(0, $cars)) {
            $this->cars = $this->session->cars = $this->orm->storeCars->findBy(['deleted' => false])->fetchPairs(null, 'id');
            $this->redrawControl('occupancyTableFilter');
        } else {
            $this->cars = $this->session->cars = $cars;
        }
        $this->redrawControl('occupancyTable');
    }

    public function handleSetMonths(): void
    {
        $months = $this->getPresenter()->getParameter('months') ?? [];
        if (in_array(0, $months)) {
            $this->months = $this->session->months = array_keys(DateTime::CZ_MONTHS);
            $this->redrawControl('occupancyTableFilter');
        } else {
            $this->months = $this->session->months = $months;
        }
        $this->redrawControl('occupancyTable');
    }

    public function handleExport(): void
    {
        $response = (new StoreTransportService($this->orm))->exportStoreCarOccupancy(
            $this->year,
            $this->months,
            $this->cars
        );
        $this->getPresenter()->sendResponse($response);
    }

    public function render(): void
    {
        $this->template->cars = $this->orm->storeCars->findBy(['deleted' => false])->fetchAll();
        $this->template->setFile(__DIR__ . '/templates/storeCarOccupancy.latte');
        $this->template->render();
    }
}