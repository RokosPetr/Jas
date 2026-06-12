<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\DeliveryModule\Orm\Companies\DepotRepository;
use App\Modules\DeliveryModule\Orm\DepotStandChecks\DepotStandCheck;
use App\Modules\DeliveryModule\Orm\DepotStandChecks\MissingDepotStand;
use App\Modules\DeliveryModule\Orm\DepotStandRelocations\DepotStandRelocation;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\StockModule\Orm\Stands\StandNote;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Collection\Expression\LikeExpression;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro stojanu na partnerskych pobockach */
final class DepotStandsPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Stojany u partnera',
        'standCheck' => 'Kontrola stojanů',
        'stockItemStands' => 'Produkt na stojanu',
        'standRelocations' => 'Relokace stojanů'
    ];

    /** Vypis stojanu na vybrane pobocce partnera */
    public function renderDefault(): void
    {
        $depotId = $this->getSession($this->getName())->depot;
        $depot = $depotId ? $this->orm->companyDepots->getById($depotId) : null;
        $this->template->depot = $depot;
        $this->template->standNotes = $depot ? $depot->loadCurrentStandNotes(false) : [];
        $this->template->stands = $depot
            ? $this->orm->stands->findBy(['deleted' => false])
                ->orderBy(['codeFirstPart' => ICollection::ASC, 'codeSecondPart' => ICollection::ASC])
            : [];
    }

    /** Vyhledani produktu na stojanu */
    public function actionStockItemStands(int $id): void
    {
        $depot = $this->orm->companyDepots->getById($id);
        if (!$depot) {
            $this->error('Položka nenalezena.');
        }
        $this->template->depot = $depot;
    }

    /** Urcit stojan k relokaci */
    public function actionRelocateStand(int $id): void
    {
        $standNote = $this->orm->standNotes->getById($id);
        if (!$standNote || !$standNote->isActive || $standNote->isRelocating) {
            $this->error('Položka nenalezena');
        }
        $this['relocateStandForm']->setDefaults(['standNote' => $id]);
        $this->template->standNote = $standNote;
        $this->sideDialogAjaxHandler();
    }

    /** Uprava relokace stojanu */
    public function actionEditRelocation(int $id): void
    {
        $relocation = $this->orm->standRelocations->getById($id);
        if (!$relocation || $relocation->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->setView('relocateStand');
        $this->template->standNote = $relocation->standNote;
        if ($relocation->target) {
            $this['relocateStandForm']['target']->setItems([$relocation->target->id => $relocation->target->name]);
        }
        $this['relocateStandForm']->setDefaults($relocation->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Potvrzeni relokace */
    public function actionConfirmRelocation(int $id): void
    {
        $relocation = $this->orm->standRelocations->getById($id);

        if (!$relocation || $relocation->deleted || !$relocation->hasTarget) {
            $this->error('Položka nenalezena');
        }

        $currentNote = $this->orm->standNotes->getBy([
            'depot->id' => $relocation->target->id,
            'stand->id' => $relocation->stand->id,
            'removeDate' => null
        ]);

        if ($currentNote) {
            $this->flashMessage('Cíl relokace již tento stojan vlastní', self::MSG_ERROR);
            $this->redirect('standRelocations');
        }

        $relocation->standNote->removeDate = new DateTimeImmutable();
        $relocation->standNote->removeBy = $this->getSysUser();
        $this->orm->standNotes->persist($relocation->standNote);
        $newStandNote = new StandNote();
        $newStandNote->stand = $relocation->standNote->stand;
        $newStandNote->depot = $relocation->target;
        $newStandNote->date = $relocation->standNote->removeDate;
        $newStandNote->remark = $relocation->remark;
        $this->orm->standNotes->persist($newStandNote);
        $this->orm->standNotes->flush();
        $this->flashMessage('Relokace provedena');
        $this->redirect('standRelocations');
    }

    /** Zruseni relokace stojanu */
    public function actionCancelRelocateStand(int $id, bool $redirectDefault = false): void
    {
        $relocation = $this->orm->standRelocations->getById($id);
        if (!$relocation || $relocation->state !== $relocation::STATE_ACTIVE) {
            $this->error('Položka nenalezena');
        }
        $this->orm->standRelocations->cancelEntity($relocation);
        $this->redirect($redirectDefault ? 'default' : 'standRelocations');
    }

    /** Kontrola stojanu u vybrane pobocky partnera */
    public function actionStandCheck(int $id): void
    {
        $depot = $this->orm->companyDepots->getById($id);
        if (!$depot) {
            $this->error('Položka nenalezena.');
        }
        $this->template->depot = $depot;
    }

    /** Prirazeni stojanu pobocce partnera */
    public function actionAddStand(int $depot, int $stand): void
    {
        $depot = $this->orm->companyDepots->getById($depot);
        $stand = $this->orm->stands->getById($stand);
        if (!$depot || !$stand || $stand->deleted || $depot->hasStand($stand)) {
            $this->error('Položka nenalezena.');
        }
        $standNote = new StandNote();
        $standNote->stand = $stand;
        $standNote->depot = $depot;
        $standNote->date = new DateTimeImmutable();
        $this->orm->standNotes->persistAndFlush($standNote);
        $this->redirect('default');
    }

    /** Naseptavac pro vyper pobocky partnera */
    public function handleUpdateDepotSelect(): void
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

    /** Nastaveni vybrane pobocky partnera */
    public function handleSetDepot(): void
    {
        $this->getSession($this->getName())->depot = $this->getRequest()->getParameter('depot');
        $this->redrawControls(['depot-stands-table', 'stand-item-search-link']);
    }

    /** Vyhledani produktu na stojanu vybranbeho partnera */
    public function handleFindStandsByItem(): void
    {
        $this->template->stands = $this->orm->stands->findBy([
            'standNotes->depot->id' => $this->getParameter('id'),
            'standNotes->removeDate' => null,
            'plates->items->item->id' => $this->getParameter('stockItemId'),
            'deleted' => false
        ])->fetchAll();
        $this->redrawControl('item-stand-table');
    }

    protected function createComponentStandRelocations(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->standRelocations);
        $grid->addCellsTemplate(__DIR__ . '/../templates/DepotStands/grid.cells.latte');
        $grid->settings->setFulltextColumns(['depot', 'stand', 'target']);

        $grid->addColumn('depot', 'Partner');
        $grid->addColumn('stand', 'Stojan');
        $grid->addColumn('target', 'Cíl');
        $grid->addColumn('created', 'Vytvořeno')->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addColumn('remark', 'Poznámka');

        $grid->addOtherColumn('updated', 'Upraveno');
        $grid->addOtherColumn('cancelled', 'Zrušeno');

        $grid->addRowAction('editRelocation', 'Upravit', 'pencil')
            ->setCondition("\$deleted == 0");
        $grid->addRowAction('confirmRelocation', 'Potvrdit relokaci', 'thumbs-o-up')
            ->setCondition("\$state == 1 && \$hasTarget == 1")
            ->setDialog('Potvrzení', 'Opravdu chcete provest relokaci stojanu?');
        $grid->addRowAction('standNotes', 'Stojan na pobočkách', 'fort-awesome')
            ->setLink('Stock', 'Stand', ['id' => 'row->standId']);
        $grid->addRowAction('cancelRelocateStand', 'Zrušit', 'close')
            ->setCondition("\$state == 1")
            ->setDialog('Potvrzení', 'Opravdu chcete zrušit relokaci stojanu?');

        $grid->addLegend('Relokováno', 'legend_orange', "\$state == 2");
        $grid->addLegend('Zrušeno', 'legend_red', "\$deleted == 1");

        $grid->setFilterFormFactory(function (): FilterContainer {
            $form = new FilterContainer();
            $form->addSelect('state', 'Stav', [
                '' => 'Vše',
                DepotStandRelocation::STATE_ACTIVE => 'Aktivní',
                DepotStandRelocation::STATE_RELOCATED => 'Relokováno',
                DepotStandRelocation::STATE_DELETED => 'Smazáno'
            ])->setDefaultValue(DepotStandRelocation::STATE_ACTIVE);
            return $form;
        });

        return $grid;
    }

    /** Form na kontrolu stojanu */
    protected function createComponentStandCheckForm(): BaseForm
    {
        $depot = $this->orm->companyDepots->getById($this->getParameter('id'));
        $standNotes = $depot->loadCurrentStandNotes();
        $form = new BaseForm();

        foreach (array_keys($standNotes) as $key) {
            $form->addCheckbox("$key");
        }

        $form->addTextArea('remark', 'Poznámka')
            ->setHtmlAttribute('placeholder', 'poznámka...');
        $form->addSubmit('submit', 'Ukončit kontrolu');

        $form->onSuccess[] = function (array $values) use($depot, $standNotes): void {
            $standCheck = new DepotStandCheck();
            $standCheck->depot = $depot;

            foreach ($standNotes as $key => $standNote) {
                if (!$values[$key]) {
                    $missingStand = new MissingDepotStand();
                    $missingStand->standNote = $standNote;
                    $standCheck->missingStands->add($missingStand);
                }
            }

            if ($values['remark']) {
                $standCheck->remark = $values['remark'];
            }

            $this->orm->standChecks->persistAndFlush($standCheck);
            $this->flashMessage('Výsledky kontroly byly uloženy');
            $this->redirect('default');
        };

        return $form;
    }

    /** Form na oznaceni stojanu k relokaci */
    protected function createComponentRelocateStandForm(): BaseForm
    {
        $relocation = null;

        if ($this->getAction() === 'editRelocation') {
            $relocation = $this->orm->standRelocations->getById($this->getParameter('id'));
        }

        $form = new BaseForm();

        if (!$relocation) {
            $form->addHidden('standNote')->setRequired();
        }

        $form->addSelect('target', 'Cíl')
            ->setPrompt('-- Vyhledat --')
            ->getControlPrototype()->addClass('select2-ignore');
        $form->addTextArea('remark', 'Poznámka');
        $form->addSubmit('submit', 'Uložit');

        $form->onValidate[] = function (BaseForm $form): void {
            if ($this->getAction() !== 'editRelocation') {
                $standNote = $this->orm->standNotes->getById($form['standNote']->getValue());
                if (!$standNote || !$standNote->isActive || $standNote->isRelocating) {
                    $form->addError('Stojan nelze relokovat');
                    return;
                }
            }

            $targetId = $this->getRequest()->getPost('target');

            if ($targetId) {
                $targetDepot = $this->orm->companyDepots->getById($targetId);
                if ($targetDepot) {
                    $form['target']->setItems([$targetDepot->id => $targetDepot->name])->setValue($targetDepot->id);
                } else {
                    $form['target']->addError('Partnerská pobočka nenalezena');
                }
            }
        };

        $form->onSuccess[] = function (array $values): void {
            if ($this->getAction() !== 'editRelocation') {
                $this->orm->standRelocations->insertEntity(null, $values);
                $this->redirect('default');
            }

            $this->orm->standRelocations->updateEntity($this->getParameter('id'), null, $values);
            $this->redirect('standRelocations');
        };

        return $form;
    }
}
