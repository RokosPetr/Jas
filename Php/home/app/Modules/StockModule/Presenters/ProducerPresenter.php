<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Modules\Presenters\SecurePresenter;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu vyrobcu */
final class ProducerPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Výrobci',
        'edit' => 'Upravit výrobce',
        'stockGroups' => 'Druhy zboží výrobce',
        'editGroup' => 'Upravit druh zboží'
    ];

    /** Editace vyrobce - nastaveni nazvu */
    public function actionEdit(int $id): void
    {
        $producer = $this->orm->producers->getById($id);
        if (!$producer) {
            $this->error('Položka nenalezena');
        }
        $this['producerForm']->setDefaults($producer->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Vypis druhu zbozi zvoleneho vyrobce */
    public function renderStockGroups(int $id): void
    {
        $producer = $this->orm->producers->getById($id);
        if (!$producer) {
            $this->error('Položka nenalezena');
        }
        $this->template->producer = $producer;
    }

    /** Editace druhu zbozi daneho vyrobce - nastaveni nazvu */
    public function actionEditGroup(int $id): void
    {
        $stockGroup = $this->orm->stockGroups->getById($id);
        if (!$stockGroup) {
            $this->error('Položka nenalezena');
        }
        $defaults = $stockGroup->toArray(ToArrayConverter::RELATIONSHIP_AS_ID);
        $defaults['producer'] = $stockGroup->producer->name;
        $this['stockGroupForm']->setDefaults($defaults);
    }

    /** Datagrid s vyrobci */
    protected function createComponentProducers(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->producers);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Producer/grid.cells.latte');
        $grid->addColumn('number', 'Skupina')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('color', 'Barva v grafu');
        $grid->addColumn('name', 'Jméno')->enableSort();
        $grid->addRowAction('edit', 'Upravit');
        $grid->addRowAction('stockGroups', 'Skupiny zboží', 'cubes');
        return $grid;
    }

    /** Datagrid s druhy zbozi zvoleneho vyrobce */
    protected function createComponentStockGroups(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->stockGroups);
        $grid->settings->setDataSourceFilter(['producer' => $this->getParameter('id')]);
        $grid->addColumn('number', 'Číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addRowAction('editGroup', 'Upravit název', 'pencil');
        return $grid;
    }

    /** Formular na upravu nazvu vyrobce */
    protected function createComponentProducerForm(): BaseForm
    {
        $producer = $this->orm->producers->getById($this->getParameter('id'));
        $companies = $this->orm->companies->findAll()->fetchPairs('id', 'name');

        $form = new BaseForm();
        $form->addInteger('number', 'Skupina')->setDisabled();
        $nameInput = $form->addText('name', 'Jméno', null, 250);

        if ($this->getUser()->isAdmin()) {
            $nameInput->setRequired();
        } else {
            $nameInput->setDisabled();
        }

        if ($this->getUser()->isAdmin()) {
            if (!$producer->isMainProducer) {
                $parentOption = $this->orm->producers->findBy([
                    'id!=' => $this->getParameter('id'),
                    'parent->id' => null
                ])->fetchPairs('id', 'name');
                $form->addSelect('parent', 'Patří pod výrobce', ['' => '---'] + $parentOption);
            }

            $form->addSelect('company', 'Společnost', ['' => '---'] + $companies);
            $form->addCheckbox('noTransfers', 'Bez převodek do statistik');
        }

        $form->addColorPicker('color', 'Barva v grafu')->setRequired();

        $form->addSubmit('edit', 'Upravit');
        $form->onSuccess[] = function (BaseForm $form): void {
            $this->orm->producers->updateEntity($this->getParameter('id'), $form);
            $this->redirect('default');
        };
        return $form;
    }

    /** Formular na upravu nazvu druhu zbozi daneho vyrobce */
    protected function createComponentStockGroupForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('producer', 'Výrobce')->setDisabled();
        $form->addInteger('number', 'Číslo')->setDisabled();
        $form->addText('name', 'Název', null, 250)->setRequired();
        $form->addCheckbox('noTransfers', 'Bez převodek do statistik');
        $form->addSubmit('edit', 'Upravit');
        $form->onSuccess[] = function (BaseForm $form): void {
            $entity = $this->orm->stockGroups->updateEntity($this->getParameter('id'), $form);
            $this->redirect('stockGroups', ['id' => $entity->producer->id]);
        };
        return $form;
    }
}
