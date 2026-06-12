<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Component;

use App\Modules\TransportModule\Orm\Drivers\StoreDriverRepository;
use Nette\Application\UI\Control;
use Nette\Http\SessionSection;

class StoreDriverSelect extends Control
{
    private StoreDriverRepository $storeDriversRepo;
    private SessionSection $session;
    protected int $selectedStore;

    public function __construct(StoreDriverRepository $storeDriversRepo, SessionSection $session, int $selectedStore)
    {
        $this->storeDriversRepo = $storeDriversRepo;
        $this->session = $session;
        $this->selectedStore = $selectedStore;
    }

    public function handleChangeDriver(int $id): void
    {
        $this->session->selectedDriver = $id;
        $this->redirect('this');
    }

    public function render(): void
    {
        $driverFilter = ['deleted' => false];
        if (!$this->getPresenter()->getUser()->isSuperAdmin()) {
            $driverFilter['car->stores->id'] = $this->selectedStore;
        }
        $this->template->drivers = $this->storeDriversRepo->findBy($driverFilter)->fetchPairs('id', 'name');
        $this->template->selectedDriver = $this->session->selectedDriver;
        $this->template->setFile(__DIR__ . '/templates/storeDriverSelect.latte');
        $this->template->render();
    }
}