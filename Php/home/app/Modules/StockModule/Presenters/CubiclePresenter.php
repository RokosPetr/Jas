<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Core\Utils\DateTime;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\StockModule\Orm\Cubicles\Cubicle;
use App\Modules\StockModule\Orm\Cubicles\CubicleRepository;
use Contributte\PdfResponse\PdfResponse;
use Nette\Http\FileUpload;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu koji */
final class CubiclePresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Kóje',
        'add' => 'Přidat kóji',
        'edit' => 'Upravit kóji',
        'cubicleItems' => 'Položky kóje',
        'addCubicleItem' => 'Přidat položku kóje',
        'editCubicleItem' => 'Upravit položku kóje'
    ];

    protected function startup(): void
    {
        parent::startup();
        if (in_array($this->getAction(), ['addCubicleItem', 'editCubicleItem'])) {
            $this->setView('addEditCubicleItem');
        }
    }

    /** Koje */
    public function renderDefault(): void
    {
        $itemFilter = $this->orm->stockItems->getById($this->getProductFilter());
        $itemFilterCubicles = [];

        if ($itemFilter) {
            $itemFilterCubicles = $this->orm->cubicles->findBy([
                'items->item->id' => $itemFilter->id,
                'deleted' => false
            ]);
        }

        $this->template->itemFilter = $itemFilter;
        $this->template->itemFilterCubicles = $itemFilterCubicles;
    }

    /** Filtr koje dle produktu v koji */
    public function handleSetProductFilter(): void
    {
        $this->setProductFilter((int) $this->getParameter('product'));
        $this->redrawControl('cubicle-product-filter');
        $this->redrawControl('cubicle-grid-control');
    }

    /** Nahled koje */
    public function actionPreview(int $id): void
    {
        $cubicle = $this->orm->cubicles->getById($id);
        if (!$cubicle) {
            $this->error('Položka nenalezena');
        }
        $this->template->cubicle = $cubicle;
        $this->sideDialogAjaxHandler();
    }

    /** Editace koje */
    public function actionEdit(int $id): void
    {
        $cubicle = $this->orm->cubicles->getById($id);
        if (!$cubicle || $cubicle->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->picture = $cubicle->picture;
        $this['cubicleForm']['depot']->setItems([$cubicle->depot->id => $cubicle->depot->depotName]);
        if (!$cubicle->isRival) {
            $series = $this->orm->stockSeries->getBy(['name' => $cubicle->name]);
            if ($series) {
                $this['cubicleForm']['series']->setItems([$series->id => $series->name])->setDefaultValue($series->id);
            }
        }
        $this['cubicleForm']->setDefaults($cubicle->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Smazani koje */
    public function actionDelete(int $id): void
    {
        $cubicle = $this->orm->cubicles->getById($id);
        if (!$cubicle || $cubicle->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->cubicles->cancelEntity($cubicle);
        $this->flashMessage('Položka byla smazána');
        $this->redirect('default');
    }

    /** Obnoveni koje */
    public function actionRestore(int $id): void
    {
        $cubicle = $this->orm->cubicles->getById($id);
        if (!$cubicle || !$cubicle->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->cubicles->restoreEntity($cubicle);
        $this->flashMessage('Položka byla obnovena');
        $this->redirect('default');
    }

    /** Smazani obrazku koje */
    public function actionRemovePicture(): void
    {
        $id = (int) $this->getRequest()->getPost('id');
        $cubicle = $this->orm->cubicles->getBy(['picture->id' => $id]);

        if (!$cubicle) {
            $this->sendErrorJson(404, 'Soubor nenalezen');
        }

        $picture = $cubicle->picture;
        $cubicle->picture = null;
        $this->orm->cubicles->persistAndFlush($cubicle);
        $this->orm->files->removeFile($picture);
        $this->sendSuccessJson();
    }

    /** Polozky koje */
    public function actionCubicleItems(int $id): void
    {
        $cubicle = $this->orm->cubicles->getById($id);
        if (!$cubicle || $cubicle->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->cubicle = $cubicle;
    }

    /** Pridani polozky do koje */
    public function actionAddCubicleItem(int $id): void
    {
        $cubicle = $this->orm->cubicles->getById($id);
        if (!$cubicle || $cubicle->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->cubicle = $cubicle;
    }

    /** Upraveni polozky v koje */
    public function actionEditCubicleItem(int $id, bool $defaultAction = false): void
    {
        $cubicleItem = $this->orm->cubicleItems->getById($id);
        if (!$cubicleItem || $cubicleItem->cubicle->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->cubicle = $cubicleItem->cubicle;
        $this['cubicleItemForm']['item']->setItems([$cubicleItem->item->id => $cubicleItem->item->title]);
        $this['cubicleItemForm']->setDefaults($cubicleItem->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Odstraneni polozky z koje */
    public function actionDeleteCubicleItem(int $id, bool $defaultAction = false): void
    {
        $cubicleItem = $this->orm->cubicleItems->getById($id);
        if (!$cubicleItem || $cubicleItem->cubicle->deleted) {
            $this->error('Položka nenalezena');
        }
        $cubicle = $cubicleItem->cubicle;
        $this->orm->cubicleItems->removeAndFlush($cubicleItem);
        $this->flashMessage('Položka byla odstraněna');
        if ($defaultAction) {
            $this->redirect('default');
        }
        $this->redirect('cubicleItems', ['id' => $cubicle->id]);
    }

    /** Export koje do PDF */
    public function actionExportPdf(int $id, bool $showPrice): void
    {
        $cubicle = $this->orm->cubicles->getById($id);
        if (!$cubicle || $cubicle->deleted) {
            $this->error('Položka nenalezena');
        }
        $template = $this->createTemplate();
        $template->setFile(__DIR__ . '/../templates/Cubicle/pdf/cubicle.latte');
        $template->cubicle = $cubicle;
        $template->showPrice = $showPrice;

        $pdf = new PdfResponse($template);
        $pdf->setDocumentTitle($cubicle->title);
        $pdf->styles = file_get_contents(__DIR__ . '/../templates/Cubicle/pdf/style.css');
        $this->sendResponse($pdf);
    }

    /** Datagrid s kojemi */
    protected function createComponentCubicles(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->cubicles);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Cubicle/grid.cells.latte');
        $grid->settings->setFulltextColumns(['code', 'depotName', 'name']);

        $grid->addColumn('code', 'ID')->enableSort();
        $grid->addColumn('depotName', 'Zákazník');
        $grid->addColumn('name', 'Série (Název)')->enableSort();
        $grid->addColumn('date', 'Datum');
        $grid->addColumn('size', 'Velikost (m2)')->numberFormat(2);
        $grid->addColumn('itemCount', 'Položky');
        $grid->addColumn('producers', 'Výrobci');
        $grid->addColumn('remark', 'Poznámka');
        $grid->addColumn('activityPeriod', 'Vystaveno');
        $grid->addColumn('cancelled', 'Zrušeno');

        $grid->addTopAction('add', 'Přidat');
        $grid->addRowAction('preview', 'Náhled')->setSideDialog();
        $grid->addRowAction('cubicleItems', 'Položky', 'list-ul')->setCondition("\$deleted == 0");
        $grid->addRowAction('edit', 'Upravit')->setCondition("\$deleted == 0");
        $grid->addRowAction('delete', 'Zrušit')->setCondition("\$deleted == 0");
        $grid->addRowAction('restore', 'Obnovit')->setCondition("\$deleted == 1");

        $grid->addLegend('Konkurenční kóje', 'legend_orange', "\$tag == 0");
        $grid->addLegend('Nová', 'legend_azure', "\$deleted == 0 && \$tag == " . Cubicle::TAG_TO_BUILD_UP);
        $grid->addLegend('Přelepit', 'legend_green', "\$deleted == 0 && \$tag == " . Cubicle::TAG_TO_GLUE_OVER);
        $grid->addLegend('Zrušená', 'legend_red', "\$deleted == 1");

        $grid->addOtherColumn('created', 'Vytvořil');
        $grid->addOtherColumn('updated', 'Upravil');

        $grid->setFilterFormFactory(function (): FilterContainer {
            $names = array_unique($this->orm->cubicles->findBy(['isRival' => false])
                ->orderBy('name')->fetchPairs(null, 'name'));
            $depots = $this->orm->companyDepots->findBy([
                'id' => array_unique($this->orm->cubicles->findAll()->fetchPairs(null, 'depot->id'))
            ])->orderBy('company->name', ICollection::DESC)->fetchPairs('id', 'name');
            $producers = $this->orm->producers->findAll()->orderBy('number')->fetchPairs('id', 'name');
            $form = new FilterContainer();
            $form->addSelect('name', 'Série', ['' => 'Vše'] + array_combine($names, $names));
            $form->addSelect('depot', 'Zákazník', ['' => 'Vše'] + $depots);
            $form->addSelect('producers', 'Výrobce', ['' => 'Vše'] + $producers);
            $form->addSelect('isRival', 'Vlastnictví', ['' => 'Vše', 0 => 'Naše kóje', 1 => 'Konkurenční kóje']);
            $form->addSelect('tag', 'Označení', ['' => 'Vše'] + Cubicle::TAGS_LABELS);
            $form->addSelect('deleted', 'Stav', [
                '' => 'Vše',
                '0' => 'Pouze nezrušené',
                '1' => 'Pouze zrušené'
            ])->setDefaultValue('0');

            return $form;
        });

        return $grid;
    }

    /** Datagrid s polozkami v koji */
    protected function createComponentCubicleItems(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->cubicleItems);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Cubicle/items.grid.cells.latte');
        $grid->settings->setDataSourceFilter(['cubicle->id' => $this->getParameter('id')])
            ->setFulltextColumns(['item']);

        $grid->addColumn('item', 'Položka')->enableSort();
        $grid->addColumn('quantity', 'Množství')->numberFormat(2);
        $grid->addColumn('unit', 'Jednotka');
        $grid->addColumn('price', 'Cena/Jednotka')->numberFormat(2);

        $grid->addTopAction('addCubicleItem', 'Přidat Položku', ['id' => $this->getParameter('id')]);
        $grid->addRowAction('editCubicleItem', 'Upravit', 'pencil');
        $grid->addRowAction('deleteCubicleItem', 'Smazat', 'trash')
            ->setDialog('Potvrzení', 'Přejete si opravdu odstranit položku?');

        return $grid;
    }

    /** Formular pro koji */
    protected function createComponentCubicleForm(): BaseForm
    {
        $cubicle = $this->getAction() === 'edit' ? $this->orm->cubicles->getById($this->getParameter('id')) : null;
        $form = new BaseForm();

        if ($cubicle) {
            $form->addText('code', 'ID')
                ->setValue($cubicle->code)
                ->setDisabled();
        } else {
            $form->addInteger('codeFirstPart', 'ID')
                ->setRequired()
                ->addRule(BaseForm::RANGE, null, [1, 999]);
        }

        $form->addSelect('depot', 'Zákazník')
            ->setPrompt('-- Vyhledat položku --')
            ->getControlPrototype()->addClass('select2-ignore');
        $form->addCheckbox('isRival', 'Konkurenční kóje')
            ->addCondition(BaseForm::EQUAL, true)
            ->toggle('cubicle-name')
            ->toggle('cubicle-series', false);
        $form->addText('name', 'Název', null, 250)
            ->setOption('id', 'cubicle-name')
            ->addConditionOn($form['isRival'], BaseForm::EQUAL, true)
            ->setRequired();
        $form->addSelect('series', 'Série')
            ->setOption('id', 'cubicle-series')
            ->setPrompt('-- Vyhledat položku --')
            ->getControlPrototype()->addClass('select2-ignore');
        $form->addSelect('month', 'Měsíc', DateTime::CZ_MONTHS)
            ->setRequired()
            ->setDefaultValue(date('n'));
        $form->addInteger('year', 'Rok')
            ->setRequired()
            ->addRule(BaseForm::RANGE, null, [1900, 9999])
            ->setDefaultValue(date('Y'));
        $form->addText('size', 'Velikost (m2)')
            ->setRequired()
            ->addRule(BaseForm::FLOAT)
            ->addRule(BaseForm::RANGE, null, [0, 1000]);
        $form->addTextArea('remark', 'Poznámka');
        $pictureUpload = $form->addUpload('picture', 'Foto')->addRule(BaseForm::IMAGE);
        $form->addRadioList('tag', 'Označení', Cubicle::TAGS_LABELS)
            ->setRequired()
            ->setDefaultValue(Cubicle::TAG_TO_BUILD_UP);

        if ($cubicle && $cubicle->picture) {
            $pictureUpload->setOption('description', $cubicle->picture->name);
        }

        $form->addSubmit('submit', 'Uložit');

        $form->onValidate[] = function (BaseForm $form): void {
            $depot = $this->orm->companyDepots->getById($this->getRequest()->getPost('depot'));
            if (!$depot) {
                $form['depot']->addError('Položka je povinná');
            } else {
                $form['depot']->setItems([$depot->id => $depot->companyName])->setValue($depot->id);
            }
            if ($form['isRival']->getValue()) {
                return;
            }
            $series = $this->orm->stockSeries->getById($this->getRequest()->getPost('series'));
            if (!$series) {
                $form['series']->addError('Položka je povinná');
                return;
            }
            $form['series']->setItems([$series->id => $series->name])->setValue($series->id);
            $form['name']->setValue($series->name);
        };

        $form->onSuccess[] = function (array $values) use ($cubicle): void {
            /** @var FileUpload $picture */
            $picture = $values['picture'];
            unset($values['picture']);

            if ($cubicle) {
                $this->orm->cubicles->updateEntity($cubicle->id, null, $values);
            } else {
                $values['codeSecondPart'] = $this->orm->cubicles->loadNewCodeNumber($values['codeFirstPart']);
                $cubicle = $this->orm->cubicles->insertEntity(null, $values);
            }

            if ($picture->hasFile()) {
                if ($cubicle->picture) {
                    $this->orm->files->updateFile($cubicle->picture, $picture);
                } else {
                    $cubicle->picture = $this->orm->files->createFile($picture, CubicleRepository::IMAGE_DIR . "/$cubicle->id");
                    $this->orm->cubicles->persistAndFlush($cubicle);
                }
            }

            $this->flashMessage('Kóje byla uložena');
            $this->redirect('default');
        };

        return $form;
    }

    /** Formular pro polozku koje */
    protected function createComponentCubicleItemForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addSelect('item', 'Položka')
            ->setPrompt('-- Vyhledat položku --')
            ->getControlPrototype()->addClass('select2-ignore');
        $form->addText('quantity', 'Množství')
            ->setRequired()
            ->addRule(BaseForm::FLOAT)
            ->addRule(BaseForm::RANGE, null, [0, 1000]);
        $form->addSubmit('submit', 'Uložit');

        $form->onValidate[] = function (BaseForm $form): void {
            $itemId = $this->getRequest()->getPost('item');
            if (!$itemId) {
                $form['item']->addError('Položka je povinná.');
                return;
            }
            $item = $this->orm->stockItems->getById($itemId);
            if (!$item) {
                $form['item']->addError('Položka nebyla v systému nalezena.');
            } else {
                $form['item']->setItems([$item->id => $item->title])
                    ->setValue($item->id);
            }
        };

        $form->onSuccess[] = function (array $values): void {
            $isEdit = $this->getAction() === 'editCubicleItem';
            $id = $this->getParameter('id');

            $cubicle = !$isEdit
                ? $this->orm->cubicles->getById($id)
                : $this->orm->cubicleItems->getById($id)->cubicle;

            if (!$isEdit) {
                $values['cubicle'] = $cubicle->id;
            }

            $isEdit
                ? $this->orm->cubicleItems->updateEntity($id, null, $values)
                : $this->orm->cubicleItems->insertEntity(null, $values);

            $this->flashMessage('Položka byla uložena');

            if ($this->getParameter('defaultAction')) {
                $this->redirect('default');
            }

            $this->redirect('cubicleItems', ['id' => $cubicle->id]);
        };

        return $form;
    }

    private function setProductFilter(int $product): void
    {
        $this->getSession('cubicleProductFilter')->product = $product;
    }

    private function getProductFilter(): int
    {
        return $this->getSession('cubicleProductFilter')->product ?? 0;
    }
}
