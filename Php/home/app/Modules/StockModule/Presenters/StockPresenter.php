<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Presenters;

use App\Core\Component\Datagrid\BaseColumn;
use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\CliModule\Service\WarehouseImporter;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\StockModule\Orm\StockItems\StockItem;
use Nette\Application\UI\InvalidLinkException;
use Nette\Http\FileUpload;
use Nextras\Dbal\Result\Row;
use Nextras\Orm\Entity\ToArrayConverter;
use Nextras\Orm\Exception\InvalidArgumentException;

/** Presenter pro praci s nabizenym sortimentem */
final class StockPresenter extends SecurePresenter
{
    /** @inject */
    public WarehouseImporter $warehouseImporter;

    public array $titles = [
        'default' => 'Sortiment',
        'edit' => 'Upravit sortiment',
        'importStatus' => 'Import statusů',
        'importQuantities' => 'Import pal/bal množství',
        'importProducers' => 'Import vyrobcu',
        'importSeries' => 'Import serií'
    ];

    /** Editace polozky sortimentu */
    public function actionEdit(int $id): void
    {
        $stockItem = $this->orm->stockItems->getById($id);
        if (!$stockItem) {
            $this->error('Položka nenalezena');
        }
        $groups = $this->orm->stockGroups->findBy(['producer->id' => $stockItem->producer->id ?? 0])
            ->orderBy('number')->fetchPairs('id', 'title');
        $defaults = $stockItem->toArray(ToArrayConverter::RELATIONSHIP_AS_ID);
        $form = $this['stockForm'];

        if (count($groups)) {
            $form['group']->setItems($groups);
        } else {
            $form['group']->setDisabled();
        }

        $form->setDefaults($defaults);
    }

    /** Export polozek sortimentu - optimalizace kvuli omezene pameti */
    public function actionExportStocks(): void
    {
        $grid = $this['stocks'];
        $grid->createComponentForm();
        $filter = $grid->getMergedFilterDataSource();
        $order = $grid->orderColumn ? [$grid->orderColumn, strtoupper($grid->orderType)] : [];
        $columns = array_map(fn(BaseColumn $column) => $column->label, $grid->getColumns());
        $response = $this->exporter->arrayToCsv(
            $this->getExportData($filter, $order, $columns),
            'sortimentVO.csv'
        );
        $this->sendResponse($response);
    }

    /** Grid s polozkama nabizeneho sortimentu */
    protected function createComponentStocks(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->stockItems);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Stock/grid.cells.latte');
        $grid->settings->setFulltextColumns(['regNumber', 'name', 'storageCatalog'])
            ->showExport('csv', 'sortimentVO', ':Stock:Stock:exportStocks');
        $grid->setMultiWordSearch();

        $grid->addColumn('regNumber', 'Index')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('storageCatalog', 'Katalog');
        $grid->addColumn('producer', 'Výrobce')->enableSort();
        $grid->addColumn('seriesName', 'Série');
        $grid->addColumn('group', 'Zboží');
        $grid->addColumn('mainStorageQuantity', 'Zásoba');
        $grid->addColumn('unit', 'mj');

        if ($this->getUser()->isAdmin()) {
            $grid->addColumn('price', 'Cena')->enableSort();
            $grid->addLegend('Zrušená položka', 'legend_red', "\$isActive == 0");
        }

        $grid->addColumn('status', 'Status');
        $grid->addColumn('statusChangedAt', 'Změna')->dateFormat(DATE)->enableSort()->disableExport();
        $grid->addColumn('minOrder', 'Minimum')->enableSort();
        $grid->addColumn('palette', 'Paleta')->enableSort();
        $grid->addColumn('package', 'Balení')->enableSort();

        $grid->addTopAction('importStatus', 'Importovat status');
        $grid->addTopAction('importQuantities', 'Importovat pal/bal množství');
        $grid->addTopAction('importProducers', 'Importovat vyrobce');
        $grid->addTopAction('importSeries', 'Importovat serie');
        $grid->addTopAction('importNames', 'Importovat názvy');
        $grid->addRowAction('edit', 'Upravit');

        $grid->setFilterFormFactory(function (): FilterContainer {
            $producers = $this->orm->producers->loadProducerFilterOption();
            $series = $this->orm->stockSeries->findAll()->orderBy('name')->fetchPairs('id', 'name');

            $form = new FilterContainer();
            $form->addContainer('statusChangedAt');
            $form->addDateFrom('statusChangedAt', 'od');
            $form->addDateTo('statusChangedAt', 'do');
            $form->addMultiSelect('producer', 'Výrobce', $producers);
            $form->addMultiSelect('series', 'Série', $series);
            $form->addSelect('status', 'Status', ['' => 'Nevyřazeno'] + StockItem::STATUSES + ['all' => 'Vše']);
            return $form;
        });

        return $grid;
    }

    /** Formular pro editaci polozky sortimentu */
    protected function createComponentStockForm(): BaseForm
    {
        $producers = ['' => '-- Vyberte --'] + $this->orm->producers->findAll()->orderBy('number')->fetchPairs('id', 'title');
        $form = new BaseForm();
        $form->addText('regNumber', 'Registrační číslo')->setDisabled();

        if ($this->getUser()->isAllowed(':Stock:Stock:editName')) {
            $form->addText('name', 'Název', null, 255)->setRequired();
        } else {
            $form->addText('name', 'Název', null, 255)->setDisabled();
        }

        if ($this->getUser()->isAdmin()) {
            $form->addSelect('producer', 'Výrobce', $producers)->setRequired();
            $form->addSelect('group', 'Druh zboží')->checkDefaultValue(false)->setRequired();
            $form->addText('palette', 'Množství na paletě')
                ->addRule(BaseForm::FLOAT)
                ->addRule(BaseForm::MIN, null, 0.1);
            $form->addText('package', 'Množství v balení')
                ->addRule(BaseForm::FLOAT)
                ->addRule(BaseForm::MIN, null, 0.1);
            $form->addSelect('unit', 'Jednotka', $this->orm->stockUnits->findAll()->fetchPairs('id', 'name'))
                ->setRequired();
        } else {
            $form->addSelect('producer', 'Výrobce', $producers)->setDisabled();
            $form->addSelect('group', 'Druh zboží')->setDisabled();
            $form->addText('palette', 'Množství na paletě')->setDisabled();
            $form->addText('package', 'Množství v balení')->setDisabled();
            $form->addSelect('unit', 'Jednotka', $this->orm->stockUnits->findAll()->fetchPairs('id', 'name'))
                ->setDisabled();
        }

        if (!$this->getUser()->isAllowed(':Stock:Stock:editName') || $this->getUser()->isAdmin()) {
            $form->addSelect('status', 'Status', StockItem::STATUSES)->setRequired();
            $form->addInteger('minOrder', 'Minimální objednávka')
                ->addRule(BaseForm::MIN, null, 1);
        }

        $form->addSubmit('edit', 'Upravit');

        $form->onSuccess[] = function (array $values): void {
            $stockItem = $this->orm->stockItems->getById($this->getParameter('id'));

            if (isset($values['status']) && $stockItem->status !== $values['status']) {
                $values['statusChangedAt'] = new \DateTime();
                $values['statusChangedBy'] = $this->getUser()->getId();
            }

            $this->orm->stockItems->updateEntity($stockItem->id, null, $values);

            $this->flashMessage('Položka byla upravena');
            $this->redirect('default');
        };

        return $form;
    }

    /**
     * Formular pro import dat k polozkam sortimentu
     *  - status polozek
     *  - pal/bal mnozstvi
     *  - vyrobce, skupiny zbozi a jednotky
     */
    protected function createComponentStockDataImportForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addUpload('import', 'Soubor k importu')
            ->setRequired()
            ->addRule(BaseForm::PATTERN, 'Pouze csv soubor', '.*\.csv$');
        $form->addSubmit('send', 'Importovat');

        $form->onSuccess[] = function (array $values): void {
            /** @var FileUpload $fileUpload */
            $fileUpload = $values['import'];
            $action = $this->getAction();

            switch ($action) {
                case 'importStatus':
                    $error = $this->warehouseImporter->importItemStatuses($fileUpload->contents, $this->getSysUser());
                    break;
                case 'importQuantities':
                    $error = $this->warehouseImporter->importItemQuantities($fileUpload->contents);
                    break;
                case 'importProducers':
                    $error = $this->warehouseImporter->updateItemProducers($fileUpload->contents);
                    break;
                case 'importSeries':
                    $error = $this->warehouseImporter->updateItemSeries($fileUpload->contents);
                    break;
                case 'importNames':
                    $error = $this->warehouseImporter->updateItemNames($fileUpload->contents);
                    break;
                default:
                    throw new InvalidLinkException("Neznama akce '$action' pro import dat k polozkam sortimentu");
            }

            if ($error) {
                $this->flashMessage($error, self::MSG_ERROR);
                $this->redirect('this');
            }

            $this->flashMessage('Import proběhl úspěšně');
            $this->redirect('default');
        };

        return $form;
    }

    /** Export polozek z datagridu */
    private function getExportData(array $filter, array $order, array $columns): array
    {
        $queryResult = $this->orm->stockItems->loadExportData($filter, $order);
        $exportData = [];

        /** @var Row[] $rowData */
        foreach ($queryResult as $rowData) {
            $exportRow = [];
            foreach ($columns as $colName => $colLabel) {
                switch ($colName) {
                    case 'regNumber':
                    case 'name':
                    case 'producer':
                    case 'storageCatalog':
                    case 'unit':
                    case 'seriesName':
                    case 'minOrder':
                        $exportRow[$colLabel] = $rowData->$colName;
                        break;
                    case 'price':
                    case 'palette':
                    case 'package':
                        $exportRow[$colLabel] = $rowData->$colName
                            ? str_replace('.', ',', (string) $rowData->$colName)
                            : '';
                        break;
                    case 'group':
                        $exportRow[$colLabel] = $rowData->groupName;
                        break;
                    case 'status':
                        $exportRow[$colLabel] = StockItem::STATUSES[$rowData->$colName];
                        break;
                    case 'statusChangedAt':
                        $exportRow[$colLabel] = $rowData->$colName
                            ? $rowData->$colName->format('d.m.Y')
                            : '';
                        break;
                    case 'mainStorageQuantity':
                        $quantity = in_array($rowData->producerNumber, StockItem::OSTRAVA_MAIN_STORAGE_PRODUCERS)
                            ? $rowData->OstravaQuantity
                            : $rowData->HlucinQuantity;
                        $exportRow[$colLabel] = $quantity ? (string) $quantity : '0';
                        break;
                    default:
                        throw new InvalidArgumentException("Unknown column $colName");
                }
            }
            $exportData[] = $exportRow;
        }

        return $exportData;
    }
}
