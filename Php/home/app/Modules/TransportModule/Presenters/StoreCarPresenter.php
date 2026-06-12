<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\TransportModule\Orm\Cars\StoreCar;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu vozidel maloobchodu */
final class StoreCarPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Dodávky maloobchodu',
        'add' => 'Přidat dodávku',
        'edit' => 'Upravit dodávku'
    ];

    /** Uprava vozidla */
    public function actionEdit(int $id): void
    {
        $car = $this->orm->storeCars->getById($id);
        if (!$car || $car->deleted) {
            $this->error('Položka nenalezena');
        }
        $this['storeCarForm']->setDefaults($car->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Smazani vozidla */
    public function actionDelete(int $id): void
    {
        $car = $this->orm->storeCars->getById($id);
        if (!$car || $car->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->storeCars->cancelEntity($car);
        $this->flashMessage('Dodávka byla odstraněna');
        $this->redirect('default');
    }

    /** Obnoveni smazaneho vozidla */
    public function actionRestore(int $id): void
    {
        $car = $this->orm->storeCars->getById($id);
        if (!$car || !$car->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->storeCars->restoreEntity($car);
        $this->flashMessage('Dodávka byla obnovena');
        $this->redirect('default');
    }

    /** Datagrid s vozidly */
    protected function createComponentStoreCars(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->storeCars);
        $grid->addCellsTemplate(__DIR__ . '/../templates/StoreCar/grid.cells.latte');
        $grid->addColumn('licensePlate', 'SPZ')->enableSort();
        $grid->addColumn('homeStore', 'Domovská pobočka');
        $grid->addColumn('driver', 'Řidič');
        $grid->addColumn('weightCapacity', 'Nosnost');
        $grid->addColumn('stores', 'Pobočky');

        $grid->addOtherColumn('id', 'ID')->enableSort();
        $grid->addOtherColumn('created', 'Vytvořeno');
        $grid->addOtherColumn('updated', 'Upraveno');
        $grid->addOtherColumn('cancelled', 'Smazáno');

        $grid->addTopAction('add', 'Přidat dodávku');
        $grid->addTopAction('default', 'Rozvoz')->setLink('Transport', 'StoreTransport');
        $grid->addRowAction('edit', 'Upravit')->setCondition("\$deleted == 0");
        $grid->addRowAction('delete', 'Smazat')->setCondition("\$deleted == 0");
        $grid->addRowAction('restore', 'Obnovit')->setCondition("\$deleted == 1");

        $grid->addLegend('Smazané', 'legend_red', "\$deleted == 1");

        $grid->setFilterFormFactory(function (): FilterContainer {
            $form = new FilterContainer();
            $form->addSelect('deleted', 'Stav', [
                '' => 'Vše',
                '0' => 'Pouze nesmazané dodávky',
                '1' => 'Pouze smazané dodávky'
            ])->setDefaultValue('0');
            return $form;
        });

        return $grid;
    }

    /** Formular pro upravu vozisla */
    protected function createComponentStoreCarForm(): BaseForm
    {
        $drivers = $this->orm->storeDrivers->findBy(['deleted' => false])->fetchPairs('id', 'name');
        $stores = $this->orm->stores->findAll()->fetchPairs('id', 'name');

        $form = new BaseForm();
        $form->addText('licensePlate', 'SPZ', null, 50)
            ->setRequired();
        $form->addSelect('driver', 'Řidič', ['' => 'Nevybráno'] + $drivers)
            ->checkDefaultValue(false);
        $form->addInteger('weightCapacity', 'Nosnost (kg)')
            ->setRequired()
            ->addRule(BaseForm::RANGE, null, [1, 50000]);
        $form->addMultiSelect('stores', 'Pobočky', $stores);
        $form->addSelect('homeStore', 'Domovská pobočka', $stores)
            ->setPrompt('--Vyberte--');
        $form->addSubmit($this->action, $this->action === 'add' ? 'Přidat' : 'Upravit');

        $form->onSuccess[] = function (BaseForm $form): void {
            /** @var StoreCar $car */
            $car = $this->action === 'add'
                ? $this->orm->storeCars->insertEntity($form)
                : $this->orm->storeCars->updateEntity($this->getParameter('id'), $form);

            if ($car->driver) {
                $carWithDriver = $this->orm->storeCars->getBy(['id!=' => $car->id, 'driver->id' => $car->driver->id]);

                if ($carWithDriver) {
                    $carWithDriver->driver = null;
                    $this->orm->storeCars->persistAndFlush($carWithDriver);
                }
            }

            $this->flashMessage('Dodávka byla ' . ($this->action === 'add' ? 'přidána': 'upravena'));
            $this->redirect('default');
        };
        return $form;
    }
}
