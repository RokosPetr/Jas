<?php
declare(strict_types=1);

namespace App\Modules\WikiModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\WikiModule\Orm\WikiItems\WikiItem;
use App\Modules\WikiModule\Orm\WikiItems\WikiParam;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\ToArrayConverter;

class WikiItemPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'ORM Wiki',
        'add' => 'Přidat Wiki položku',
        'edit' => 'Upravit Wiki položku',
        'params' => 'Atributy wiki položky',
        'addParam' => 'Přidat Wiki atribut',
        'editParam' => 'Upravit Wiki atribut'
    ];

    protected function startup(): void
    {
        parent::startup();
        if (in_array($this->getAction(), ['addParam', 'editParam'])) {
            $this->setView('addEditParam');
        }
    }

    /** Nahled wiki polozky */
    public function actionPreview(int $id): void
    {
        $wikiItem = $this->orm->wikiItems->getById($id);
        if (!$wikiItem) {
            $this->error('Položka nenalezena');
        }
        $this->template->wikiItem = $wikiItem;
        $this->sideDialogAjaxHandler();
    }

    /** Editace wiki polozky */
    public function actionEdit(int $id): void
    {
        $wikiItem = $this->orm->wikiItems->getById($id);
        if (!$wikiItem) {
            $this->error('Položka nenalezena');
        }
        $this['wikiItemForm']->setDefaults($wikiItem->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Editace wiki polozky */
    public function actionDelete(int $id): void
    {
        $wikiItem = $this->orm->wikiItems->getById($id);
        if (!$wikiItem || $wikiItem->hasParams) {
            $this->error('Položka nenalezena');
        }
        $this->orm->wikiItems->removeAndFlush($wikiItem);
        $this->flashMessage('Položka odstraněna');
        $this->redirect('default');
    }

    /** Atributy wiki polozky */
    public function actionParams(int $id): void
    {
        $wikiItem = $this->orm->wikiItems->getById($id);
        if (!$wikiItem) {
            $this->error('Položka nenalezena');
        }
        $this->template->wikiItem = $wikiItem;
    }

    /** Pridani atributu k wiki polozce */
    public function actionAddParam(int $id): void
    {
        $wikiItem = $this->orm->wikiItems->getById($id);
        if (!$wikiItem) {
            $this->error('Položka nenalezena');
        }
        $this->template->wikiItem = $wikiItem;
    }

    /** Uprava atributu k wiki polozce */
    public function actionEditParam(int $id): void
    {
        $wikiParam = $this->orm->wikiParams->getById($id);
        if (!$wikiParam) {
            $this->error('Položka nenalezena');
        }
        $this->template->wikiItem = $wikiParam->item;
        $this['wikiParamForm']->setDefaults($wikiParam->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Uprava atributu k wiki polozce */
    public function actionDeleteParam(int $id): void
    {
        $wikiParam = $this->orm->wikiParams->getById($id);
        if (!$wikiParam) {
            $this->error('Položka nenalezena');
        }
        $wikiId = $wikiParam->item->id;
        $this->orm->wikiParams->removeAndFlush($wikiParam);
        $this->flashMessage('Položka odstraněna');
        $this->redirect('params', ['id' => $wikiId]);
    }

    /** Grid s wiki entitama */
    protected function createComponentWikiItems(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->wikiItems);
        $grid->addCellsTemplate(__DIR__ . '/../templates/WikiItem/grid.cells.latte');
        $grid->settings->setFulltextColumns(['name'])
            ->setForceOrder(['module' => ICollection::ASC]);

        $grid->addColumn('name', 'Entita')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('module', 'Modul');
        $grid->addColumn('remark', 'Popis');
        $grid->addColumn('attributes', 'Vlastnosti');

        $grid->addTopAction('add', 'Přidat');
        $grid->addRowAction('preview', 'Náhled')->setSideDialog();
        $grid->addRowAction('params', 'Atributy', 'list-ul');
        $grid->addRowAction('edit', 'Upravit');
        $grid->addRowAction('delete', 'Smazat')->setCondition("\$hasParams == 0");

        $grid->setFilterFormFactory(function (): FilterContainer {
            $form = new FilterContainer();
            $form->addSelect('module', null, ['' => 'Vše'] + WikiItem::MODULES_LABELS);
            return $form;
        });

        return $grid;
    }

    /** Grid s atributy wiki polozky */
    protected function createComponentWikiParams(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->wikiParams);
        $grid->addCellsTemplate(__DIR__ . '/../templates/WikiItem/params.grid.cells.latte');
        $grid->settings->setDataSourceFilter(['item' => $this->getParameter('id')])
            ->setFulltextColumns(['name'])
            ->setForceOrder(['order' => BaseDatagrid::ORDER_ASC]);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('type', 'Typ');
        $grid->addColumn('remark', 'Popis');

        $grid->addLegend('Virtuální atribut', 'legend_orange', "\$virtual == 1");

        $grid->addTopAction('addParam', 'Přidat', ['id' => $this->getParameter('id')]);
        $grid->addRowAction('editParam', 'Upravit', 'pencil');
        $grid->addRowAction('deleteParam', 'smazat', 'trash')
            ->setDialog('Potvrzení', 'Opravdu chcete odstranit tuto položku?');
        return $grid;
    }

    /** Form pro wiki entitu */
    protected function createComponentWikiItemForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('name', 'Název', null, 100)->setRequired();
        $form->addSelect('module', 'Modul', WikiItem::MODULES_LABELS)->setRequired();
        $form->addTextArea('remark', 'Popis')->setRequired();
        $form->addCheckbox('creatable', 'Creatable');
        $form->addCheckbox('updatable', 'Updatable');
        $form->addCheckbox('deletable', 'Deletable');
        $form->addCheckbox('lockable', 'Lockable');
        $form->addSubmit('submit', 'Uložit');

        $form->onSuccess[] = function (array $values): void {
            $this->getAction() === 'add'
                ? $this->orm->wikiItems->insertEntity(null, $values)
                : $this->orm->wikiItems->updateEntity($this->getParameter('id'), null, $values);
            $this->flashMessage('Položka uložena');
            $this->redirect('default');
        };

        return $form;
    }

    /** Form pro wiki atribut */
    protected function createComponentWikiParamForm(): BaseForm
    {
        $wikiItem = $this->getAction() === 'addParam'
            ? $this->orm->wikiItems->getById($this->getParameter('id'))
            : $this->orm->wikiParams->getById($this->getParameter('id'))->item;
        $form = new BaseForm();
        $form->addText('name', 'Název', null, 100)->setRequired();
        $form->addSelect('type', 'Typ', WikiParam::TYPES_LABELS)->setRequired();
        $form->addInteger('order', 'Pořadí')
            ->setRequired()
            ->addRule(BaseForm::RANGE, null, [1, 100])
            ->setDefaultValue($this->orm->wikiParams->getNextOrder($wikiItem->id));
        $form->addCheckbox('virtual', 'Virtuální atribut');
        $form->addTextArea('remark', 'Popis')->setRequired();
        $form->addSubmit('submit', 'Uložit');

        if ($this->getAction() === 'addParam') {
            $form->addSubmit('continue', 'Uložit a přidat další');
        }

        $form->onSuccess[] = function (BaseForm $form, array $values): void {
            $isCreate = $this->getAction() === 'addParam';

            if ($isCreate) {
                $values['item'] = $this->getParameter('id');
            }

            $wikiParam = $isCreate
                ? $this->orm->wikiParams->insertEntity(null, $values)
                : $this->orm->wikiParams->updateEntity($this->getParameter('id'), null, $values);

            $this->flashMessage('Položka uložena');

            $isCreate && $form['continue']->isSubmittedBy()
                ? $this->redirect('addParam', ['id' => $wikiParam->item->id])
                : $this->redirect('params', ['id' => $wikiParam->item->id]);
        };

        return $form;
    }
}