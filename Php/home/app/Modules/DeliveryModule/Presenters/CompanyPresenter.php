<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu partneru (spolecnosti) */
final class CompanyPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Partneři',
        'preview' => 'Náhled partnera',
        'edit' => 'Upravit partnera'
    ];

    /** Editace udaju o spolecnosti */
    public function actionPreview(int $id): void
    {
        $company = $this->orm->companies->getById($id);
        if (!$company) {
            $this->error('Položka nenalezena');
        }
        $this->template->company = $company;
        $this->sideDialogAjaxHandler();
    }

    /** Oznacena partnera, u ktereho se ignoruji nakupy ve statistikach */
    public function actionTakingsIgnore(int $id): void
    {
        $company = $this->orm->companies->getById($id);
        if (!$company) {
            $this->error('Položka nenalezena');
        }
        $company->takingsIgnore = !$company->takingsIgnore;
        $this->orm->companies->persistAndFlush($company);
        $this->flashMessage("Změna ignorace nákupů proběhla úspěšně (ičo $company->ico)");
        $this->redirect('default');
    }

    /** Editace udaju o spolecnosti */
    public function actionEdit(int $id): void
    {
        $company = $this->orm->companies->getById($id);
        if (!$company) {
            $this->error('Položka nenalezena');
        }
        $this['companyForm']->setDefaults($company->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Datagrid partneru */
    protected function createComponentCompanies(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->companies);
        // Fyzicke osoby a Koupelny JaS
        $filter = ['ico!=' => [0, Store::INTERNAL_ICO]];

        $grid->settings->setFulltextColumns(['icoString', 'name'])
            ->setDataSourceFilter($filter);

        $grid->addColumn('icoString', 'IČO')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('address', 'Adresa');
        $grid->addColumn('storeIds', 'MOP registrace');
        $grid->addColumn('countryCode', 'Země')->enableSort();

        $grid->addRowAction('preview', 'Pobočky')->setSideDialog();
        $grid->addRowAction('takingsIgnore', 'Ignorace nákupů', 'car');
        $grid->addRowAction('edit', 'Upravit');

        if ($this->getUser()->isAllowed(':Delivery:Company:takingsIgnore')) {
            $grid->addLegend('Ignorovány nákupy', 'legend_orange', "\$takingsIgnore == 1");

            $grid->setFilterFormFactory(function (): FilterContainer {
                $takingsIgnoreOption = [
                    '' => 'Vše',
                    1 => 'Pouze nákupy ignorovány',
                    0 => 'Pouze bez ignorovaných nákupů'
                ];
                $form = new FilterContainer();
                $form->addSelect('takingsIgnore', 'Ignorovány nákupy', $takingsIgnoreOption);
                return $form;
            });
        }

        return $grid;
    }

    /** Form na editaci udaju o spolecnosti */
    protected function createComponentCompanyForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('ico', 'IČO')->setDisabled();
        $form->addText('name', 'Název', null, 255)->setRequired();
        $form->addText('countryCode', 'Země', null, 3)->setRequired()
            ->setDefaultValue('CZ');
        $informationContainer = $form->addContainer('information');
        $informationContainer->addText('dic', 'DIČ', null, 30);
        $informationContainer->addText('street', 'Ulice', null, 255);
        $informationContainer->addText('city', 'Město', null, 255);
        $informationContainer->addText('zipCode', 'PSČ')
            ->addRule(BaseForm::PATTERN, 'Musí obsahovat pouze čísla', '[0-9]*')
            ->addRule(BaseForm::LENGTH, 'Musí obsahovat 5 číslic', 5);
        $form->addSubmit('edit', 'Upravit');

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->orm->companies->updateEntity($this->getParameter('id'), $form);
            $this->flashMessage('Položka byla upravena');
            $this->redirect('default');
        };

        return $form;
    }
}
