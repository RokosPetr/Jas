<?php
declare(strict_types=1);

namespace App\Modules\MtzModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\MtzModule\Orm\MtzItems\MtzGroup;
use App\Modules\MtzModule\Orm\MtzItems\MtzItem;
use App\Modules\MtzModule\Orm\MtzItems\MtzItemRepository;
use App\Modules\Presenters\SecurePresenter;
use Nette\Http\FileUpload;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu polozek MTZ */
final class MtzItemPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'MTZ položky',
        'add' => 'Přidat MTZ položku',
        'edit' => 'Upravit MTZ položku',
        'mtzGroups' => 'MTZ skupiny',
        'addMtzGroup' => 'Přidat MTZ skupinu',
        'editMtzGroup' => 'Upravit MTZ skupinu'
    ];

    protected function startup(): void
    {
        parent::startup();
        if (in_array($this->getAction(), ['addMtzGroup', 'editMtzGroup'])) {
            $this->setView('addEditMtzGroup');
        }
    }

    /** Editace MTZ polozky */
    public function actionEdit(int $id): void
    {
        $mtzItem = $this->orm->mtzItems->getById($id);
        if (!$mtzItem) {
            $this->error('Položka nenalezena');
        }
        $this['mtzItemForm']->setDefaults($mtzItem->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
        $this->template->picture = $mtzItem->picture;
    }

    /** Smazani MTZ polozky */
    public function actionDelete(int $id): void
    {
        $mtzItem = $this->orm->mtzItems->getById($id);
        if (!$mtzItem || $mtzItem->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->mtzItems->cancelEntity($mtzItem);
        $this->flashMessage('Položka byla odstraněna');
        $this->redirect('default');
    }

    /** Obnoveni MTZ polozky */
    public function actionRestore(int $id): void
    {
        $mtzItem = $this->orm->mtzItems->getById($id);
        if (!$mtzItem || !$mtzItem->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->mtzItems->restoreEntity($mtzItem);
        $this->flashMessage('Položka byla obnovena');
        $this->redirect('default');
    }

    /** Pridani MTZ skupiny */
    public function actionAddMtzGroup(): void
    {
        $maxOrder = $this->orm->mtzGroups->findAll()->orderBy('order', ICollection::DESC)
            ->fetch()->order ?? 0;
        $this['mtzGroupForm']['order']->setDefaultValue($maxOrder + 1);
    }

    /** Editace MTZ skupiny */
    public function actionEditMtzGroup(int $id): void
    {
        $mtzGroup = $this->orm->mtzGroups->getById($id);
        if (!$mtzGroup) {
            $this->error('Položka nenalezena');
        }
        $this['mtzGroupForm']->setDefaults($mtzGroup->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Smazani MTZ skupiny */
    public function actionDeleteMtzGroup(int $id): void
    {
        $mtzGroup = $this->orm->mtzGroups->getById($id);
        if (!$mtzGroup || $mtzGroup->hasItems) {
            $this->error('Položka nenalezena');
        }
        $this->orm->mtzGroups->removeAndFlush($mtzGroup);
        $this->flashMessage('Položka byla odstraněna');
        $this->redirect('mtzGroups');
    }

    /** Datagrid MTZ polozek */
    protected function createComponentMtzItems(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->mtzItems);
        $grid->addCellsTemplate(__DIR__ . '/../templates/MtzItem/grid.cells.latte');
        $grid->settings->setFulltextColumns(['regNumber', 'name']);

        if (!$this->getUser()->isAllowed(':Mtz:MtzItem:restore')) {
            $grid->settings->setDataSourceFilter(['deleted' => 0]);
        } else {
            $grid->addOtherColumn('cancelled', 'Smazal');
        }

        $grid->addColumn('regNumber', 'Registrační číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();
        //$grid->addColumn('description', 'Popis');
        //$grid->addColumn('remark', 'Poznámka');
        $grid->addColumn('packageTitle', 'Balení');
        $grid->addColumn('group', 'Skupina');

        $grid->addOtherColumn('id', 'ID')->enableSort();
        $grid->addOtherColumn('created', 'Vytvořil');
        $grid->addOtherColumn('updated', 'Upravil');

        $grid->addTopAction('add', 'Přidat');
        $grid->addTopAction('mtzGroups', 'Skupiny MTZ');
        $grid->addRowAction('edit', 'Upravit')->setCondition("\$deleted == 0");
        $grid->addRowAction('delete', 'Smazat')->setCondition("\$deleted == 0");
        $grid->addRowAction('restore', 'Obnovit')->setCondition("\$deleted == 1");

        $grid->setFilterFormFactory(function (): FilterContainer {
            $groups = $this->orm->mtzGroups->findAll()->fetchPairs('id', 'name');
            $form = new FilterContainer();
            $form->addSelect('group', 'Skupina', ['' => 'Vše'] + $groups);

            if ($this->getUser()->isAllowed(':Mtz:MtzItem:restore')) {
                $form->addSelect('deleted', 'Stav', [
                    '' => 'Vše',
                    '0' => 'Pouze nesmazané položky',
                    '1' => 'Pouze smazané položky'
                ])->setDefaultValue('0');
            }

            return $form;
        });

        return $grid;
    }

    /** Datagrid MTZ skupin */
    protected function createComponentMtzGroups(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->mtzGroups);
        $grid->addColumn('order', 'Číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('tax', 'Daň')->enableSort();

        $grid->addTopAction('addMtzGroup', 'Přidat MTZ skupinu');
        $grid->addTopAction('default', 'MTZ položky');
        $grid->addRowAction('editMtzGroup', 'Upravit', 'pencil');
        $grid->addRowAction('deleteMtzGroup', 'Smazat', 'trash')
            ->setCondition("\$hasItems == 0")
            ->setDialog('Potvrzení', 'Opravdu chcete odstranit tuto položku?');

        return $grid;
    }

    /** Form na editaci MTZ polozky */
    protected function createComponentMtzItemForm(): BaseForm
    {
        $mtzItem = $this->getAction() === 'edit' ? $this->orm->mtzItems->getById($this->getParameter('id')) : null;
        $groups = $this->orm->mtzGroups->findAll()->fetchPairs('id', 'name');
        $form = new BaseForm();
        $form->addInteger('regNumber', 'Registrační číslo')->setRequired();
        $form->addSelect('group', 'Skupina', $groups)->setRequired();
        $form->addText('name', 'Název', null, 255)->setRequired();
        $form->addText('description', 'Popis', null, 500)->setRequired();
        $form->addTextArea('remark', 'Poznámka');
        $form->addInteger('packageSize', 'Velikost balení');
        $form->addSelect('packageUnit', 'Jednotka', ['' => '-'] + MtzItem::UNITS_LABELS)
            ->setDefaultValue(MtzItem::UNIT_PEACES);
        $form->addSelect('orderUnit', 'Jednotka pro objednání', ['' => '-'] + MtzItem::UNITS_LABELS)
            ->setDefaultValue(MtzItem::UNIT_PACKAGES);
        $pictureUpload = $form->addUpload('picture', 'Foto')->addRule(BaseForm::IMAGE);

        if ($mtzItem && $mtzItem->picture) {
            $pictureUpload->setOption('description', $mtzItem->picture->name);
        }

        $form->addSubmit('edit', 'Uložit');
        $form->addSubmit('cancel', 'Zpět')->setValidationScope([])
            ->getControlPrototype()->addClass('btn btn-danger');

        $form->onSuccess[] = function (BaseForm $form, array $values): void {
            if ($form['cancel']->isSubmittedBy()) {
                $this->redirect('default');
            }
            /** @var FileUpload $picture */
            $picture = $values['picture'];
            unset($values['picture']);
            /** @var MtzItem $mtzItem */
            $mtzItem = $this->getAction() === 'add'
                ? $this->orm->mtzItems->insertEntity(null, $values)
                : $this->orm->mtzItems->updateEntity($this->getParameter('id'), null, $values);

            if ($picture->hasFile()) {
                if ($mtzItem->picture) {
                    $this->orm->files->updateFile($mtzItem->picture, $picture);
                } else {
                    $mtzItem->picture = $this->orm->files->createFile($picture, MtzItemRepository::IMAGE_DIR . "/$mtzItem->id");
                    $this->orm->mtzItems->persistAndFlush($mtzItem);
                }
            }

            $this->flashMessage('MTZ položka uložena');
            $this->redirect('default');
        };

        return $form;
    }

    /** Form na editaci MTZ skupiny */
    protected function createComponentMtzGroupForm(): BaseForm
    {
        $groups = $this->orm->mtzGroups->findAll()->fetchPairs('id', 'name');
        $form = new BaseForm();
        $form->addInteger('order', 'Číslo')
            ->setRequired()
            ->addRule(BaseForm::RANGE, null, [1, 100]);
        $form->addSelect('parent', 'Skupina', $groups);
        $form->addText('name', 'Název', null, 255)->setRequired();
        $form->addInteger('tax', 'Daň (%)')
            ->setRequired()
            ->addRule(BaseForm::RANGE, null, [0, 100])
            ->setDefaultValue(MtzGroup::BASE_TAX);
        $form->addSubmit('edit', 'Uložit');
        $form->addSubmit('cancel', 'Zpět')->setValidationScope([])
            ->getControlPrototype()->addClass('btn btn-danger');

        $form->onSuccess[] = function (BaseForm $form, array $values): void {
            if ($form['cancel']->isSubmittedBy()) {
                $this->redirect('mtzGroups');
            }
            $this->orm->mtzGroups->beginTransaction();
            $newOrder = $values['order'];

            if ($this->getAction() === 'addMtzGroup') {
                $values['order'] = 101;
                $mtzGroup = $this->orm->mtzGroups->insertEntity(null, $values);
                $this->orm->mtzGroups->changeOrder($mtzGroup, $newOrder);
                $this->orm->mtzGroups->flush();
            } else {
                $mtzGroup = $this->orm->mtzGroups->getById($this->getParameter('id'));
                if ($newOrder !== $mtzGroup->order) {
                    $this->orm->mtzGroups->changeOrder($mtzGroup, $newOrder);
                    $this->orm->mtzGroups->flush();
                }
                $this->orm->mtzGroups->updateEntity($mtzGroup->id, null, $values);
            }

            $this->orm->mtzGroups->commitTransaction();
            $this->flashMessage('MTZ skupina uložena');
            $this->redirect('mtzGroups');
        };

        return $form;
    }
}
