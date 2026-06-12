<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\CliModule\Service\WarehouseImporter;
use App\Modules\Presenters\SecurePresenter;
use Nette\Http\FileUpload;

/** Presenter pro spravu povinneho sortimentu */
final class ObligatoryItemPresenter extends SecurePresenter
{
    /** @inject */
    public WarehouseImporter $warehouseImporter;

    public array $titles = [
        'default' => 'Povinný sortiment',
        'add' => 'Přidat povinný sortiment',
        'edit' => 'Upravit povinný sortiment',
        'import' => 'Import povinných položek'
    ];

    /**
     * Editace povinne polozky sortimentu
     * - nastaveni minimalniho mnozstvi na pobocce a minimalni objednavane mnozstvi
     */
    public function actionEdit(int $id): void
    {
        $obligatoryItem = $this->orm->obligatoryItems->getById($id);
        if (!$obligatoryItem) {
            $this->error('Položka nenalezena');
        }
        $this->template->obligatoryItem = $obligatoryItem;
        $this['obligatoryItemForm']->setDefaults([
            'quantity' => $obligatoryItem->quantity,
            'minOrder' => $obligatoryItem->minOrder
        ]);
    }

    /** Odstraneni povinne polozky sortimentu */
    public function actionDelete(int $id): void
    {
        $item = $this->orm->obligatoryItems->getById($id);
        if (!$item) {
            $this->error('Položka nenalezena');
        }
        $this->orm->obligatoryItems->removeAndFlush($item);
        $this->redirect('default');
    }

    /** Datagrid povinneho sortimentu */
    protected function createComponentObligatoryItems(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->obligatoryItems);
        $grid->settings->setFulltextColumns(['name', 'regNumber']);
        $grid->setMultiWordSearch();
        $grid->addColumn('regNumber', 'Registrační číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('producer', 'Výrobce')->enableSort();
        $grid->addColumn('series', 'Série');
        $grid->addColumn('stockGroup', 'Druh zboží');
        $grid->addColumn('quantity', 'Minimální množství')->enableSort();
        $grid->addColumn('minOrder', 'Objednávané množství')->enableSort();

        $grid->addTopAction('add', 'Přidat');
        $grid->addTopAction('import', 'Importovat');
        $grid->addRowAction('edit', 'Upravit množství');
        $grid->addRowAction('delete', 'Smazat');

        $grid->setFilterFormFactory(function (): FilterContainer {
            $producers = $this->orm->obligatoryItems->loadProducersForFilter();
            $series = $this->orm->obligatoryItems->loadSeriesForFilter();
            $form = new FilterContainer();
            $form->addMultiSelect('producerId', 'Výrobce', $producers)
                ->checkDefaultValue(false)
                ->getControlPrototype()->addClass('multiple-select2');
            $form->addMultiSelect('series', 'Série', $series)
                ->checkDefaultValue(false)
                ->getControlPrototype()->addClass('multiple-select2');
            return $form;
        });
        return $grid;
    }

    /** Formular pro editaci polozky povinneho sortimentu */
    protected function createComponentObligatoryItemForm(): BaseForm
    {
        $form = new BaseForm();

        if ($this->action === 'add') {
            $form->addSelect('item', 'Produkt')
                ->getControlPrototype()->addClass('select2-ignore');
        }

        $form->addText('quantity', 'Minimální množství')
            ->setRequired()
            ->addRule(BaseForm::FLOAT)
            ->addRule(BaseForm::RANGE, null, [0.1, 100000]);

        $form->addText('minOrder', 'Objednávané množství')
            ->setRequired()
            ->addRule(BaseForm::FLOAT)
            ->addRule(BaseForm::RANGE, null, [0.1, 100000]);

        $form->addSubmit($this->action, $this->action === 'add' ? 'Přidat' : 'Upravit');

        $form->onValidate[] = function (BaseForm $form): void {
            if ($this->action !== 'add') {
                return;
            }

            $stockItemId = $this->getHttpRequest()->getPost('item');

            if (!$stockItemId) {
                $form['item']->addError('Toto pole je povinné.');
                return;
            }

            $stockItem = $this->orm->stockItems->getById($stockItemId);

            if (!$stockItem) {
                $form['item']->addError('Produkt nenalezen.');
            }

            if ($this->orm->obligatoryItems->getBy(['item->id' => $stockItem->id])) {
                $form['item']->addError('Produkt již má zadané povinné množství na pobočkách.');
            }
        };

        $form->onSuccess[] = function (array $values): void {
            if ($this->action === 'add') {
                $values['item'] = $this->getHttpRequest()->getPost('item');
                $this->orm->obligatoryItems->insertEntity(null, $values);
            } else {
                $this->orm->obligatoryItems->updateEntity($this->getParameter('id'), null, $values);
            }

            $this->redirect('default');
        };

        return $form;
    }

    /** Formular pro vlozeni csv souboru s nastavenim povinneho sortimentu */
    protected function createComponentObligatoryItemsImportForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addUpload('import', 'Soubor k importu')
            ->setRequired()
            ->addRule(BaseForm::PATTERN, 'Pouze csv soubor', '.*\.csv$');
        $form->addSubmit('send', 'Importovat');

        $form->onSuccess[] = function (array $values): void {
            /** @var FileUpload $fileUpload */
            $fileUpload = $values['import'];
            $error = $this->warehouseImporter->importObligatoryItems($fileUpload->contents);

            if ($error) {
                $this->flashMessage($error, self::MSG_ERROR);
                $this->redirect('this');
            }

            $this->flashMessage('Import proběhl úspěšně');
            $this->redirect('default');
        };
        return $form;
    }
}
