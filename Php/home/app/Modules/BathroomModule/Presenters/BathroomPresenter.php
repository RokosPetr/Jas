<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\BathroomModule\Orm\Bathrooms\BathPicture;
use App\Modules\BathroomModule\Orm\Bathrooms\BathPictureRepository;
use App\Modules\BathroomModule\Orm\Bathrooms\Bathroom;
use App\Modules\BathroomModule\Orm\Parameters\BathParameter;
use App\Modules\Presenters\SecurePresenter;
use Nette\Http\FileUpload;
use Nextras\Orm\Entity\ToArrayConverter;

class BathroomPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Koupelny',
        'preview' => 'Náhled koupelny',
        'add' => 'Přidat koupelnu',
        'edit' => 'Upravit koupelnu',
        'itemLinks' => 'Odkazové body',
        'addItemLink' => 'Přidat odkazový bod',
        'editItemLink' => 'Upravit odkazový bod'
    ];

    protected function startup(): void
    {
        parent::startup();
        if (in_array($this->getAction(), ['addItemLink', 'editItemLink'])) {
            $this->setView('addEditItemLink');
        }
    }

    /** Nahled koupelny */
    public function actionPreview(int $id): void
    {
        $bathroom = $this->orm->bathrooms->getById($id);
        if (!$bathroom) {
            $this->error('Položka nenalezena');
        }
        $this->template->bathroom = $bathroom;
        $this->sideDialogAjaxHandler();
    }

    /** Uprava koupelny */
    public function actionEdit(int $id): void
    {
        $bathroom = $this->orm->bathrooms->getById($id);
        if (!$bathroom || $bathroom->deleted) {
            $this->error('Položka nenalezena');
        }
        $defaults = $bathroom->toArray(ToArrayConverter::RELATIONSHIP_AS_ID);
        $defaults['options'] = [];
        unset($defaults['pictures']);

        foreach ($bathroom->options as $option) {
            if ($option->parameter->id === BathParameter::TYPE) {
                $defaults['options'][$option->parameter->id] = $option->id;
            } else {
                $defaults['options'][$option->parameter->id] ??= [];
                $defaults['options'][$option->parameter->id][] = $option->id;
            }
        }

        $this['bathroomForm']->setDefaults($defaults);
        $this->template->bathroom = $bathroom;
    }

    /** Smazani koupelny */
    public function actionDelete(int $id): void
    {
        $bathroom = $this->orm->bathrooms->getById($id);
        if (!$bathroom || $bathroom->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->bathrooms->cancelEntity($bathroom);
        $this->flashMessage('Koupelna byla smazána');
        $this->redirect('default');
    }

    /** Obnoveni koupelny */
    public function actionRestore(int $id): void
    {
        $bathroom = $this->orm->bathrooms->getById($id);
        if (!$bathroom || !$bathroom->deleted) {
            $this->error('Položka nenalezena');
        }
        $duplicity = $this->orm->bathrooms->getBy([
            'name' => $bathroom->name,
            'deleted' => false
        ]);
        if ($duplicity) {
            $this->flashMessage('Koupelnu nelze obnovit, koupelna s tímto názvem existuje', self::MSG_ERROR);
            $this->redirect('default');
        }
        $this->orm->bathrooms->restoreEntity($bathroom);
        $this->flashMessage('Koupelna byla obnovena');
        $this->redirect('default');
    }

    /** Odkazove body koupelny */
    public function actionItemLinks(int $id): void
    {
        $bathroom = $this->orm->bathrooms->getById($id);
        if (!$bathroom || $bathroom->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->bathroom = $bathroom;
    }

    /** Pridani odkazoveho bodu koupelny */
    public function actionAddItemLink(int $id): void
    {
        $bathroom = $this->orm->bathrooms->getById($id);
        if (!$bathroom || $bathroom->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->bathroom = $bathroom;
        $this->template->posX = 50;
        $this->template->posY = 50;
    }

    /** Uprava odkazoveho bodu koupelny */
    public function actionEditItemLink(int $id): void
    {
        $itemLink = $this->orm->bathItemLinks->getById($id);
        if (!$itemLink || $itemLink->bathroom->deleted) {
            $this->error('Položka nenalezena');
        }
        $this['bathItemLinkForm']['item']->setItems([$itemLink->item->id => $itemLink->item->title]);
        $this['bathItemLinkForm']->setDefaults($itemLink->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
        $this->template->bathroom = $itemLink->bathroom;
        $this->template->posX = $itemLink->positionX;
        $this->template->posY = $itemLink->positionY;
        $this->template->prevId = $itemLink->getSiblingId(true);
        $this->template->nextId = $itemLink->getSiblingId();
    }

    /** Uprava odkazoveho bodu koupelny */
    public function actionDeleteItemLink(int $id): void
    {
        $itemLink = $this->orm->bathItemLinks->getById($id);
        if (!$itemLink || $itemLink->bathroom->deleted) {
            $this->error('Položka nenalezena');
        }
        $bathroomId = $itemLink->bathroom->id;
        $this->orm->bathItemLinks->removeAndFlush($itemLink);
        $this->flashMessage('Položka byla smazána');
        $this->redirect('itemLinks', ['id' => $bathroomId]);
    }

    /** Grid s koupelnama */
    protected function createComponentBathrooms(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->bathrooms);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Bathroom/grid.cells.latte');
        $grid->settings->setFulltextColumns(['name', 'optionList']);
        $grid->setMultiWordSearch();

        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('optionList', 'Parametry');
        $grid->addColumn('itemLinksCount', 'Odkazové body')->enableSort();
        $grid->addColumn('priority', 'Priorita')->enableSort();
        $grid->addColumn('averageRating', 'Hodnocení')->numberFormat(1)->enableSort();

        $grid->addTopAction('add', 'Přidat');
        $grid->addRowAction('preview', 'Náhled')->setSideDialog();
        $grid->addRowAction('edit', 'Upravit')->setCondition("\$deleted == 0");
        $grid->addRowAction('itemLinks', 'Nastavení bodů', 'external-link-square')
            ->setCondition("\$deleted == 0");
        $grid->addRowAction('delete', 'Smazat')->setCondition("\$deleted == 0");
        $grid->addRowAction('restore', 'Obnovit')
            ->setCondition("\$deleted == 1")
            ->setDialog('Potvrzení', 'Opravdu chcete položku obnovit?');

        $grid->addOtherColumn('id', 'ID')->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addOtherColumn('cancelled', 'Smazáno');

        if (!$this->getUser()->isAllowed(':Bathroom:Bathroom:restore')) {
            $grid->settings->setDataSourceFilter(['deleted' => 0]);
        } else {
            $grid->addLegend('Smazano', 'legend_red', "\$deleted == 1");

            $grid->setFilterFormFactory(function (): FilterContainer {
                $form = new FilterContainer();

                if ($this->getUser()->isAllowed(':Bathroom:Bathroom:restore')) {
                    $form->addSelect('deleted', 'Stav', [
                        '' => 'Vše',
                        '0' => 'Pouze nesmazané',
                        '1' => 'Pouze smazané'
                    ])->setDefaultValue('0');
                }

                return $form;
            });
        }

        return $grid;
    }

    /** Grid s odkazovymi body */
    protected function createComponentBathItemLinks(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->bathItemLinks);
        $grid->settings->setDataSourceFilter(['bathroom->id' => $this->getParameter('id')])
            ->setFulltextColumns(['item']);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Bathroom/itemLinks.grid.cells.latte');

        $grid->addColumn('item', 'Položka')->enableSort();
        $grid->addColumn('link', 'Odkaz');
        $grid->addColumn('position', 'Pozice');
        $grid->addOtherColumn('id', 'ID')->enableSort(BaseDatagrid::ORDER_DESC);

        $grid->addTopAction('addItemLink', 'Přidat', ['id' => $this->getParameter('id')]);
        $grid->addRowAction('editItemLink', 'Upravit', 'pencil');
        $grid->addRowAction('deleteItemLink', 'Smazat', 'trash')
            ->setDialog('Potvrzení', 'Opravdu chcete položku odstranit?');

        return $grid;
    }

    /** Formular koupelny */
    protected function createComponentBathroomForm(): BaseForm
    {
        $bathPictures = $this->getAction() === 'edit'
            ? $this->orm->bathrooms->getById($this->getParameter('id'))->pictures->toCollection()
                ->fetchPairs('position', 'picture->name')
            : [];
        $form = new BaseForm();
        $form->addText('name', 'Název', null, 450)->setRequired();
        $form->addInteger('priority', 'Priorita')
            ->setRequired()
            ->setDefaultValue(5)
            ->addRule(BaseForm::RANGE, null, [1, 10]);
        $optionContainer = $form->addContainer('options');

        foreach ($this->orm->bathParameters->findAll()->orderBy('order') as $parameter) {
            $options = $parameter->options->toCollection()->orderBy('order')->fetchPairs('id', 'name');

            if ($parameter->id === BathParameter::TYPE) {
                $optionContainer->addRadioList((string) $parameter->id, $parameter->name, $options)->setRequired();
            } elseif ($parameter->id === BathParameter::SERIES) {
                $optionContainer->addMultiSelect((string) $parameter->id, $parameter->name, $options);
            } else {
                $optionContainer->addCheckboxList((string) $parameter->id, $parameter->name, $options);
            }
        }

        $pictureContainer = $form->addContainer('pictures');
        $picturePositions = [];

        for ($position = 1; $position <= BathPictureRepository::MAX_POSITION; $position++) {
            $picturePositions[$position] = "Pozice $position";
            $upload = $pictureContainer->addUpload((string) $position, "Obrázek pozice $position")
                ->addRule(BaseForm::IMAGE);

            if (isset($bathPictures[$position])) {
                $upload->setOption('description', $bathPictures[$position]);
            }
        }

        $upload = $pictureContainer->addUpload((string) BathPictureRepository::POSITION_3D, 'Obrázek 360°')
            ->addRule(BaseForm::IMAGE);

        if (isset($bathPictures[BathPictureRepository::POSITION_3D])) {
            $upload->setOption('description', $bathPictures[BathPictureRepository::POSITION_3D]);
        }

        $form->addSelect('linkPicturePosition', 'Obrázek s odkazy', $picturePositions)
            ->setRequired()
            ->setDefaultValue(5)
            ->getControlPrototype()->addClass('select2-ignore');

        $form->addInteger('virtualPictureFocus', 'Střed obrázku 360°')
            ->setRequired()
            ->setDefaultValue(180)
            ->addRule(BaseForm::RANGE, null, [0, 360])
            ->getControlPrototype()->addClass('range-slider hide');

        $form->addSubmit('submit', 'Uložit a zpět');
        $form->addSubmit('reloadSubmit', 'Uložit');
        $form->addSubmit('cancel', 'Zpět')->setValidationScope([])
            ->getControlPrototype()
            ->addClass( 'btn btn-danger');

        $form->onValidate[] = function (BaseForm $form, array $values): void {
            $nameFilter = [
                'name' => $values['name'],
                'deleted' => false,
                'options->id' => $values['options'][BathParameter::TYPE]
            ];

            if ($this->getAction() === 'edit') {
                $nameFilter['id!='] = $this->getParameter('id');
            }

            if ($this->orm->bathrooms->getBy($nameFilter)) {
                $form['name']->addError('Koupelna s tímto názvem a typem již existuje!');
            }
        };

        $form->onSuccess[] = function (BaseForm $form, array $values): void {
            if ($form['cancel']->isSubmittedBy()) {
                $this->redirect('default');
            }

            $pictures = $values['pictures'];
            unset($values['pictures']);
            $options = [];

            foreach ($values['options'] as $optionValues) {
                foreach ((is_array($optionValues) ? $optionValues : [$optionValues]) as $optionValue) {
                    $options[] = $optionValue;
                }
            }

            $values['options'] = $options;

            if ($this->getAction() === 'add') {
                /** @var Bathroom $bathroom */
                $bathroom = $this->orm->bathrooms->insertEntity(null, $values);
            } else {
                $bathroom = $this->orm->bathrooms->updateEntity($this->getParameter('id'), null, $values);
            }

            $savedPictures = $bathroom->pictures->toCollection()->fetchPairs('position');

            /** @var FileUpload $picture */
            foreach ($pictures as $position => $picture) {
                $position = intval($position);

                if (!$picture->hasFile()) {
                    continue;
                }

                if (isset($savedPictures[$position])) {
                    $this->orm->files->updateFile($savedPictures[$position]->picture, $picture);
                } else {
                    $bathPicture = new BathPicture();
                    $bathPicture->bathroom = $bathroom;
                    $bathPicture->position = $position;
                    $bathPicture->picture = $this->orm->files->createFile(
                        $picture,
                        BathPictureRepository::IMAGE_DIR . "/$bathroom->id/$position"
                    );
                    $this->orm->bathPictures->persistAndFlush($bathPicture);
                }
            }

            $this->flashMessage('Formulář uložen');
            $form['reloadSubmit']->isSubmittedBy()
                ? $this->redirect('edit', ['id' => $bathroom->id])
                : $this->redirect('default');
        };

        return $form;
    }

    /** Formular odkazoveho bodu */
    protected function createComponentBathItemLinkForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addSelect('item', 'Produkt')
            ->setPrompt('-- Vyhledat položku --')
            ->getControlPrototype()->addClass('select2-ignore');
        $form->addText('link', 'Odkaz', null, 500)
            ->setRequired()
            ->addRule(BaseForm::URL);
        $form->addText('positionX', 'Pozice X (%)')
            ->setRequired()
            ->setDefaultValue(50)
            ->addRule(BaseForm::FLOAT)
            ->addRule(BaseForm::RANGE, null, [0, 100])
            ->setHtmlAttribute('readonly', true);
        $form->addText('positionY', 'Pozice Y (%)')
            ->setRequired()
            ->setDefaultValue(50)
            ->addRule(BaseForm::FLOAT)
            ->addRule(BaseForm::RANGE, null, [0, 100])
            ->setHtmlAttribute('readonly', true);

        $form->addSubmit('continue', 'Uložit a přidat další');
        $form->addSubmit('submit', 'Uložit');
        $form->addSubmit('cancel', 'Zpět')->setValidationScope([])
            ->getControlPrototype()
            ->addClass( 'btn btn-danger');

        $form->onValidate[] = function (BaseForm $form): void {
            if ($form['cancel']->isSubmittedBy()) {
                return;
            }
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

        $form->onSuccess[] = function (BaseForm $form, array $values): void {
            if ($form['cancel']->isSubmittedBy()) {
                $bathroom = $this->getAction() === 'addItemLink'
                    ? $this->orm->bathrooms->getById($this->getParameter('id'))
                    : $this->orm->bathItemLinks->getById($this->getParameter('id'))->bathroom;
                $this->redirect('itemLinks', ['id' => $bathroom->id]);
            }

            if ($this->getAction() === 'addItemLink') {
                $values['bathroom'] = $this->orm->bathrooms->getById($this->getParameter('id'))->id;
                $itemLink = $this->orm->bathItemLinks->insertEntity(null, $values);
            } else {
                $itemLink = $this->orm->bathItemLinks->getById($this->getParameter('id'));
                $this->orm->bathItemLinks->updateEntity($itemLink->id, null, $values);
            }

            $this->flashMessage('Položka byla uložena');

            $form['continue']->isSubmittedBy()
                ? $this->redirect('addItemLink', ['id' => $itemLink->bathroom->id])
                : $this->redirect('itemLinks', ['id' => $itemLink->bathroom->id]);
        };

        return $form;
    }
}