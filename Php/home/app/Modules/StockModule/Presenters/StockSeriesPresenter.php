<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\StockModule\Orm\StockSeries\StockSeries;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu serii vyrobku */
final class StockSeriesPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Serie',
        'add' => 'Přidat název serie',
        'edit' => 'Upravit název serie'
    ];

    /** Editace serie - nastaveni nazvu */
    public function actionEdit(int $id): void
    {
        $stockSeries = $this->orm->stockSeries->getById($id);
        if (!$stockSeries) {
            $this->error('Položka nenalezena');
        }
        $this['stockSeriesForm']->setDefaults($stockSeries->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Nahled serie */
    public function actionPreview(int $id): void
    {
        $stockSeries = $this->orm->stockSeries->getById($id);
        if (!$stockSeries) {
            $this->error('Položka nenalezena');
        }
        $this->template->stockSeries = $stockSeries;
        $this->sideDialogAjaxHandler();
    }

    /** Smazani serie */
    public function actionDelete(int $id): void
    {
        $stockSeries = $this->orm->stockSeries->getById($id);
        if (!$stockSeries) {
            $this->error('Položka nenalezena');
        }
        if ($stockSeries->hasItems) {
            $this->flashMessage('Položku nelze smazat', self::MSG_ERROR);
            $this->redirect('default');
        }
        $this->orm->stockSeries->removeAndFlush($stockSeries);
        $this->flashMessage('Položka byla odstraněna');
        $this->redirect('default');
    }

    /** Datagrid s seriemi */
    protected function createComponentStockSeries(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->stockSeries);
        $grid->settings->setFulltextColumns(['name', 'key']);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('key', 'Klíč')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addTopAction('add', 'Přidat');
        $grid->addTopAction('importNames', 'Import názvu serií');
        $grid->addRowAction('edit', 'Upravit název');
        $grid->addRowAction('preview', 'Náhled')->setSideDialog();
        $grid->addRowAction('delete', 'Smazat')->setCondition("\$hasItems == 0");

        $grid->setFilterFormFactory(function (): FilterContainer {
            $options = [
                '' => 'Vše',
                0 => 'Pouze bez produktů',
                1 => 'Pouze s produkty'
            ];
            $form = new FilterContainer();
            $form->addSelect('hasItems', 'Zobrazit', $options);
            return $form;
        });

        return $grid;
    }

    /** Formular na upravu nazvu serie */
    protected function createComponentStockSeriesForm(): BaseForm
    {
        $isEditAction = $this->getAction() === 'edit';
        $form = new BaseForm();
        $seriesKeyInput = $form->addText('key', 'Klíč');
        $form->addText('name', 'Název', null, 50)->setRequired();

        if ($isEditAction) {
            $seriesKeyInput->setDisabled();
        } else {
            $seriesKeyInput->setRequired()->addRule(
                BaseForm::PATTERN,
                'Musí obsahovat pouze malá písmena bez diakritiky, číslice, pomlčky a tečky',
                '[a-z0-9.-]*'
            );
        }

        $form->addSubmit('submit', $isEditAction ? 'Upravit' : 'Přidat');

        if (!$isEditAction) {
            $form->onValidate[] = function (BaseForm $form, array $values): void {
                $duplicateSeries = $this->orm->stockSeries->getBy(['key' => $values['key']]);
                if ($duplicateSeries) {
                    $form['key']->addError('Tento klíč již existuje!');
                }
            };
        }

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->getAction() === 'edit'
                ? $this->orm->stockSeries->updateEntity($this->getParameter('id'), $form)
                : $this->orm->stockSeries->insertEntity($form);
            $this->flashMessage('Položka byla uložena');
            $this->redirect('default');
        };
        return $form;
    }

    /** Formular na import nazvu serii */
    protected function createComponentImportSeriesNamesForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addUpload('import', 'Soubor k importu')
            ->setRequired()
            ->addRule(BaseForm::PATTERN, 'Pouze csv soubor', '.*\.csv$');
        $form->addSubmit('send', 'Importovat');
        $form->onSuccess[] = function (array $values): void {
            $fileContent = $values['import']->contents;
            $stockSeries = $this->orm->stockSeries->findAll()->fetchPairs('key');
            $separator = "\r\n";
            $line = strtok($fileContent, $separator);

            while ($line !== false) {
                $csvData = str_getcsv($line, ';');
                $name = trim($csvData[0] ?? '');
                $key = trim($csvData[1] ?? '');

                if (!$key || !isset($stockSeries[$key])) {
                    $line = strtok($separator);
                    continue;
                }

                /** @var StockSeries $editStockSeries */
                $editStockSeries = $stockSeries[$key];

                if ($editStockSeries->name !== $name) {
                    $editStockSeries->name = $name;
                    $this->orm->stockSeries->persist($editStockSeries);
                }

                $line = strtok($separator);
            }

            $this->orm->stockSeries->flush();
            $this->flashMessage('Import názvů sérií proběhl úspěšně');
            $this->redirect('default');
        };
        return $form;
    }
}
