<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Core\Orm\BaseMapper;
use App\Modules\DeliveryModule\Orm\Companies\DepotRepository;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nextras\Orm\Collection\Expression\LikeExpression;
use Nextras\Orm\Collection\ICollection;

/** Presenter pro spravu slev */
class DiscountPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Slevové skupiny',
        'stockItems' => 'Slevy na produkty',
        'stockGroups' => 'Slevy na skupiny',
        'depots' => 'Pobočky partnerů',
        'import' => 'Import slev partnerů'
    ];

    /** Uprava nazvu slevove skupiny */
    public function actionEdit(int $id): void
    {
        $discountGroup = $this->orm->discountGroups->getById($id);
        if (!$discountGroup) {
            $this->error('Položka nenalezena');
        }
        $this['editDiscountGroupForm']->setDefaults([
            'number' => $discountGroup->number,
            'name' => $discountGroup->name
        ]);
        $this->sideDialogAjaxHandler();
    }

    /** Seznam slev na produkty pro danou slevovou skupiny */
    public function actionStockItems(int $id): void
    {
        $discountGroup = $this->orm->discountGroups->getById($id);
        if (!$discountGroup) {
            $this->error('Položka nenalezena');
        }
        $this->template->discountGroup = $discountGroup;
    }

    /** Seznam slev na produktove skupiny pro danou slevovou skupiny */
    public function actionStockGroups(int $id): void
    {
        $discountGroup = $this->orm->discountGroups->getById($id);
        if (!$discountGroup) {
            $this->error('Položka nenalezena');
        }
        $this->template->discountGroup = $discountGroup;
    }

    /** Seznam pobocek obchodnich partneru pro danou slevovou skupiny */
    public function actionDepots(int $id): void
    {
        $discountGroup = $this->orm->discountGroups->getById($id);
        if (!$discountGroup) {
            $this->error('Položka nenalezena');
        }
        $this->template->discountGroup = $discountGroup;
    }

    /** Pridani pobocky partnera do slevove skupiny */
    public function actionAddDepot(int $id): void
    {
        $discountGroup = $this->orm->discountGroups->getById($id);
        if (!$discountGroup) {
            $this->error('Položka nenalezena');
        }
        $this['addDiscountDepotForm']->setDefaults(['discount' => $discountGroup->id]);
        $this->sideDialogAjaxHandler();
    }

    /** Odstraneni pobocky partnera ze slevove skupiny */
    public function actionRemoveDepot(int $id): void
    {
        $depot = $this->orm->companyDepots->getById($id);
        if (!$depot || !$depot->discountGroup) {
            $this->error('Polořka nenalezena');
        }
        $discountGroup = $depot->discountGroup;
        $depot->discountGroup = null;
        $this->orm->companyDepots->persistAndFlush($depot);
        $this->redirect('depots', ['id' => $discountGroup->id]);
    }

    /** Grid se slevovyma skupinama */
    protected function createComponentDiscountGroups(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->discountGroups);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Discount/grid.cells.latte');
        $grid->settings->setFulltextColumns(['number', 'name']);

        $grid->addColumn('number', 'Slevová skupina')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Popis');
        $grid->addColumn('stockItemCount', 'Počet produktů');
        $grid->addColumn('stockGroupCount', 'Počet skupin produktů');
        $grid->addColumn('depotCount', 'Počet partnerských poboček');

        $grid->addTopAction('import', 'Import partnerů');
        $grid->addRowAction('edit', 'Upravit název')->setSideDialog();

        return $grid;
    }

    /** Grid se slevama na produkty */
    protected function createComponentDiscountStockItems(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->discountStockItems);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Discount/items.grid.cells.latte');
        $grid->settings->setDataSourceFilter(['discountGroup->id' => $this->getParameter('id')])
            ->setFulltextColumns(['stockItem'])
            ->hideSettings()
            ->setForceOrder(['producerNumber' => ICollection::ASC, 'stockItemNumber' => ICollection::ASC]);

        $grid->addColumn('producer', 'Výrobce');
        $grid->addColumn('stockItem', 'Produkt');
        $grid->addColumn('value', 'Sleva')->enableSort();
        return $grid;
    }

    /** Grid se slevama na produktove skupiny */
    protected function createComponentDiscountStockGroups(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->discountStockGroups);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Discount/groups.grid.cells.latte');
        $grid->settings->setDataSourceFilter(['discountGroup->id' => $this->getParameter('id')])
            ->setFulltextColumns(['stockGroup'])
            ->hideSettings()
            ->setForceOrder(['producerNumber' => ICollection::ASC, 'stockGroupNumber' => ICollection::ASC]);

        $grid->addColumn('producer', 'Výrobce');
        $grid->addColumn('stockGroup', 'Druh zboží');
        $grid->addColumn('value', 'Sleva')->enableSort();

        $grid->setFilterFormFactory(function (): FilterContainer {
            $producers = $this->orm->producers->findAll()->orderBy('number')->fetchPairs('id', 'name');
            $form = new FilterContainer();
            $form->addSelect('producer', 'Výrobce', ['' => 'Vše'] + $producers);
            return $form;
        });

        return $grid;
    }

    /** Grid se slevama pro pobocky obchodnich partneru */
    protected function createComponentDiscountDepots(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->companyDepots);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Discount/depots.grid.cells.latte');
        $grid->settings->setDataSourceFilter(['discountGroup->id' => $this->getParameter('id')]);

        $grid->addColumn('company', 'Společnost');
        $grid->addColumn('title', 'Pobočka');

        $grid->addTopAction('addDepot', 'Přidat', ['id' => $this->getParameter('id')])->setSideDialog();
        $grid->addRowAction('removeDepot', 'Odstranit', 'close')
            ->setDialog('Potvrzená', 'Opravdu chcete odstratnit tuto položku?');
        return $grid;
    }

    /** Form pro zadani pobocky partnera ke slevove skupine */
    protected function createComponentAddDiscountDepotForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addSelect('depot', 'Partnerská pobočka')
            ->setPrompt('--Vyhledat--')
            ->getControlPrototype()->addClass('select2-ignore');
        $form->addHidden('discount')->setRequired();
        $form->addSubmit('submit', 'Přidat');

        $form->onValidate[] = function (BaseForm $form): void {
            $depotId = $this->getHttpRequest()->getPost('depot');

            if (!$depotId) {
                $form['depot']->addError('Toto pole je povinné.');
                return;
            }

            $depot = $this->orm->companyDepots->getById($depotId);

            if (!$depot) {
                $form['depot']->addError('Pobočka partnera nenalezena.');
            }

            $form['depot']->setItems([$depot->id => $depot->title])->setValue($depot->id);
        };

        $form->onSuccess[] = function (array $values): void {
            $discount = $this->orm->discountGroups->getById($values['discount']);
            $depot = $this->orm->companyDepots->getById($values['depot']);
            $depot->discountGroup = $discount;
            $this->orm->companyDepots->persistAndFlush($depot);
            $this->redirect('depots', ['id' => $discount->id]);
        };

        return $form;
    }

    /** Form na upravu nazvu slevove skupiny */
    protected function createComponentEditDiscountGroupForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('number', 'Slevová skupina')->setDisabled();
        $form->addText('name', 'Popis', null, 250);
        $form->addSubmit('submit', 'Uložit');
        $form->onSuccess[] = function (array $values): void {
            $discountGroup = $this->orm->discountGroups->getById($this->getParameter('id'));
            $discountGroup->name = $values['name'];
            $this->orm->discountGroups->persistAndFlush($discountGroup);
            $this->redirect('default');
        };
        return $form;
    }

    /** Form pro import slevovych skupin k pobockam partneru */
    protected function createComponentDiscountDepotImportForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addUpload('depotDiscounts', 'CSV soubor')
            ->setRequired()
            ->addRule(BaseForm::PATTERN, 'Pouze csv soubor', '.*\.csv$');
        $form->addSubmit('import', 'Importovat');

        $form->onSuccess[] = function (array $values): void {
            $result = $this->importDepotDiscounts($values['depotDiscounts']->contents);
            $this->flashMessage("Naimportováno $result->success položek, ignorováno $result->error položek");
            $this->redirect('default');
        };

        return $form;
    }

    /** Handle pro option selectu pobocek partneru */
    public function handleLoadDepotOption(): void
    {
        $search = trim($this->getParameter('search'));
        $result = [];

        if (!$search) {
            $this->sendJson($result);
        }

        $filter = ['store->id' => Store::OSTRAVA_MAIN_STORAGE];

        if (preg_match("/^\d+$/", $search)) {
            $filter['company->ico~'] = LikeExpression::contains(ltrim($search, '0'));
        } else {
            $filter['company->name~'] = LikeExpression::contains(trim($search));
        }

        $depots = $this->orm->companyDepots->findBy($filter + DepotRepository::DEALER_FILTER)
            ->orderBy('company->ico');

        foreach ($depots as $depot) {
            $result[] = [
                'id' => $depot->id,
                'text' => "$depot->companyIcoString - $depot->name"
            ];
        }

        $this->sendJson(['results' => $result]);
    }

    private function importDepotDiscounts(string $fileContent): \stdClass
    {
        $result = new \stdClass();
        $result->success = 0;
        $result->error = 0;

        if (!$fileContent) {
            return $result;
        }

        $discountGroups = $this->orm->discountGroups->findAll()->fetchPairs('number');
        $depots = $this->orm->companyDepots->loadStoreDepots(Store::OSTRAVA_MAIN_STORAGE);
        $separator = "\r\n";
        $line = strtok($fileContent, $separator);

        while ($line !== false) {
            $csvData = str_getcsv($line, ';');
            $line = strtok($separator);
            $ico = intval(trim($csvData[0] ?? ''));
            $voj = trim($csvData[1] ?? '');
            $discountNumber = intval(trim($csvData[2] ?? ''));
            $depotId = $ico . BaseMapper::DATA_STRING_SEPARATOR . $voj;

            if (!isset($depots[$depotId])) {
                $result->error++;
                continue;
            }

            if (!isset($discountGroups[$discountNumber]) && $discountNumber !== 0) {
                $result->error++;
                continue;
            }

            $depot = $this->orm->companyDepots->getById($depots[$depotId]);
            $depot->discountGroup = $discountGroups[$discountNumber] ?? null;
            $this->orm->companyDepots->persist($depot);
            $result->success++;
        }

        $this->orm->companyDepots->flush();

        return $result;
    }
}