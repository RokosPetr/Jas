<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\Presenters\SecurePresenter;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu ridicu maloobchodu */
final class StoreDriverPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Řidiči maloobchodu',
        'add' => 'Přidat řidiče',
        'edit' => 'Upravit řidiče'
    ];

    /** Uprava ridice */
    public function actionEdit(int $id): void
    {
        $driver = $this->orm->storeDrivers->getById($id);
        if (!$driver || $driver->deleted) {
            $this->error('Položka nenalezena');
        }
        $this['storeDriverForm']->setDefaults($driver->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Smazani ridice */
    public function actionDelete(int $id): void
    {
        $driver = $this->orm->storeDrivers->getById($id);
        if (!$driver || $driver->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->storeDrivers->cancelEntity($driver);
        $this->flashMessage('Řidič byl smazán');
        $this->redirect('default');
    }

    /** Obnoveni smazaneho řidiče */
    public function actionRestore(int $id): void
    {
        $driver = $this->orm->storeDrivers->getById($id);
        if (!$driver || !$driver->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->storeDrivers->restoreEntity($driver);
        $this->flashMessage('Řidič byl obnoven');
        $this->redirect('default');
    }

    /** Datagrid s ridici */
    protected function createComponentStoreDrivers(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->storeDrivers);
        $grid->settings->setFulltextColumns(['name']);
        $grid->addCellsTemplate(__DIR__ . '/../templates/StoreDriver/grid.cells.latte');
        $grid->addColumn('name', 'Jméno')->enableSort();
        $grid->addColumn('car', 'Dodávka');
        $grid->addColumn('phone', 'Telefon');

        $grid->addOtherColumn('id', 'ID')->enableSort();
        $grid->addOtherColumn('created', 'Vytvořeno');
        $grid->addOtherColumn('updated', 'Upraveno');
        $grid->addOtherColumn('cancelled', 'Smazáno');

        $grid->addTopAction('add', 'Přidat řidiče');
        $grid->addTopAction('default', 'Rozvoz')->setLink('Transport', 'StoreTransport');
        $grid->addRowAction('edit', 'Upravit')->setCondition("\$deleted == 0");
        $grid->addRowAction('delete', 'Smazat')->setCondition("\$deleted == 0");
        $grid->addRowAction('restore', 'Obnovit')->setCondition("\$deleted == 1");

        $grid->addLegend('Smazané', 'legend_red', "\$deleted == 1");

        $grid->setFilterFormFactory(function (): FilterContainer {
            $form = new FilterContainer();
            $form->addSelect('deleted', 'Stav', [
                '' => 'Vše',
                '0' => 'Pouze nesmazaní řidiči',
                '1' => 'Pouze smazaní řidiči'
            ])->setDefaultValue('0');
            return $form;
        });

        return $grid;
    }

    /** Formular pro upravu ridice */
    protected function createComponentStoreDriverForm(): BaseForm
    {
        $users = $this->orm->users->findAll()->orderBy('name')->fetchPairs('id', 'name');
        $cars = $this->orm->storeCars->findBy(['deleted' => false])->orderBy('licensePlate')->fetchPairs('id', 'licensePlate');

        $form = new BaseForm();
        $form->addText('name', 'Jméno', null, 255)
            ->setRequired();
        $form->addText('phone', 'Telefon', null, 20);
        $form->addSelect('car', 'Dodávka', ['' => 'Nevybráno'] + $cars)->checkDefaultValue(false);
        $form->addSelect('user', 'Uživatel', ['' => 'Nevybráno'] + $users);
        $form->addSubmit($this->action, $this->action === 'add' ? 'Přidat' : 'Upravit');

        $form->onSuccess[] = function (array $values): void {
            $carId = $values['car'];
            unset($values['car']);
            $driver = $this->action === 'add'
                ? $this->orm->storeDrivers->insertEntity(null, $values)
                : $this->orm->storeDrivers->updateEntity($this->getParameter('id'), null, $values);

            if ($carId) {
                $car = $this->orm->storeCars->getById($carId);
                $car->driver = $driver;
                $this->orm->storeCars->persistAndFlush($car);
            } else {
                $car = $this->orm->storeCars->getBy(['driver->id' => $driver->id]);

                if ($car) {
                    $car->driver = null;
                    $this->orm->storeCars->persistAndFlush($car);
                }
            }

            $this->flashMessage('Řidič byl ' . ($this->action === 'add' ? 'přidán': 'upraven'));
            $this->redirect('default');
        };
        return $form;
    }
}
