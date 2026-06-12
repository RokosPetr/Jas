<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\Presenters\SecurePresenter;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu sluzeb pobocek */
final class ServicePresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Služby',
        'edit' => 'Upravit službu',
        'groups' => 'Skupiny služeb',
        'editGroup' => 'Upravit skupinu služeb'
    ];

    /** Editace sluzby */
    public function actionEdit(int $id): void
    {
        $service = $this->orm->deliveryServices->getById($id);
        if (!$service) {
            $this->error('Položka nenalezena');
        }
        $this['deliveryServiceForm']->setDefaults($service->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Editace sluzby */
    public function actionEditGroup(int $id): void
    {
        $serviceGroup = $this->orm->deliveryServiceGroups->getById($id);
        if (!$serviceGroup) {
            $this->error('Položka nenalezena');
        }
        $this['deliveryServiceGroupForm']->setDefaults($serviceGroup->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Datagrid sluzeb */
    protected function createComponentDeliveryServices(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->deliveryServices);
        $grid->settings->setFulltextColumns(['regNumber', 'name']);
        $grid->addColumn('regNumber', 'Registrační číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('groupName', 'Skupina');

        $grid->addTopAction('groups', 'Skupiny služeb');
        $grid->addRowAction('edit', 'Upravit název');

        $grid->setFilterFormFactory(function (): FilterContainer {
            $groups = $this->orm->deliveryServiceGroups->findAll()->orderBy('number')->fetchPairs('id', 'title');
            $form = new FilterContainer();
            $form->addSelect('group', 'Skupina', ['' => 'Vše'] + $groups);
            return $form;
        });

        return $grid;
    }

    /** Datagrid skupin sluzeb */
    protected function createComponentDeliveryServiceGroups(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->deliveryServiceGroups);
        $grid->addColumn('number', 'Číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();

        $grid->addRowAction('editGroup', 'Upravit název', 'pencil');
        return $grid;
    }

    /** Form na editaci sluzby */
    protected function createComponentDeliveryServiceForm(): BaseForm
    {
        $groups = $this->orm->deliveryServiceGroups->findAll()->orderBy('number')->fetchPairs('id', 'title');
        $form = new BaseForm();
        $form->addText('regNumber', 'Registrační číslo')->setDisabled();
        $form->addText('name', 'Název', null, 255)->setRequired();
        $form->addSelect('group', 'Skupina', $groups)->setRequired();
        $form->addSubmit('edit', 'Upravit');

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->orm->deliveryServices->updateEntity($this->getParameter('id'), $form);
            $this->flashMessage('Služba upravena');
            $this->redirect('default');
        };

        return $form;
    }

    /** Form na editaci sluzby */
    protected function createComponentDeliveryServiceGroupForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('number', 'Číslo')->setDisabled();
        $form->addText('name', 'Název', null, 255)->setRequired();
        $form->addSubmit('edit', 'Upravit');

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->orm->deliveryServiceGroups->updateEntity($this->getParameter('id'), $form);
            $this->flashMessage('Skupina služeb upravena');
            $this->redirect('groups');
        };

        return $form;
    }
}
