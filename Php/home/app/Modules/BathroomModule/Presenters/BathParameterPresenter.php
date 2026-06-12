<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Modules\BathroomModule\Orm\Parameters\BathOptionRepository;
use App\Modules\BathroomModule\Orm\Parameters\BathParameter;
use App\Modules\Presenters\SecurePresenter;
use Nette\Http\FileUpload;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\ToArrayConverter;

class BathParameterPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Parametry koupelny',
        'add' => 'Přidat parametr',
        'edit' => 'Upravit parametr',
        'options' => 'Možnosti parametru',
        'addOption' => 'Přidat možnost parametru',
        'editOption' => 'Upravit možnost parametru'
    ];

    protected function startup(): void
    {
        parent::startup();
        if (in_array($this->getAction(), ['addOption', 'editOption'])) {
            $this->setView('addEditOption');
        }
    }

    /** Pridani parametru */
    public function actionAdd(): void
    {
        $parameter = $this->orm->bathParameters->findAll()->orderBy('order', ICollection::DESC)->fetch();
        $newOrder = $parameter ? ($parameter->order + 1) : 1;
        $this['bathParameterForm']->setDefaults(['order' => $newOrder]);
    }

    /** Uprava parametru */
    public function actionEdit(int $id): void
    {
        $parameter = $this->orm->bathParameters->getById($id);
        if (!$parameter) {
            $this->error('Položka nenalezena');
        }
        $this['bathParameterForm']->setDefaults($parameter->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Smazani parametru */
    public function actionDelete(int $id): void
    {
        $parameter = $this->orm->bathParameters->getById($id);
        if (!$parameter) {
            $this->error('Položka nenalezena');
        }
        if ($parameter->hasOptions) {
            $this->error('Položku nelze smazat');
        }
        $this->orm->bathParameters->removeAndFlush($parameter);
        $this->flashMessage('Položka byla odstraněna');
        $this->redirect('default');
    }

    /** Vypis moznosti parametru */
    public function actionOptions(int $id): void
    {
        $parameter = $this->orm->bathParameters->getById($id);
        if (!$parameter) {
            $this->error('Položka nenalezena');
        }
        $this->template->parameter = $parameter;
    }

    /** Pridani moznosti parametru */
    public function actionAddOption(int $id): void
    {
        $parameter = $this->orm->bathParameters->getById($id);
        if (!$parameter) {
            $this->error('Položka nenalezena');
        }
        $option = $this->orm->bathOptions->findBy(['parameter->id' => $id])
            ->orderBy('order', ICollection::DESC)
            ->fetch();
        $newOrder = $option ? ($option->order + 1) : 1;
        $this['bathOptionForm']->setDefaults(['order' => $newOrder]);
        $this->template->parameter = $parameter;
        $this->template->picture = null;
    }

    /** Uprava moznosti parametru */
    public function actionEditOption(int $id): void
    {
        $option = $this->orm->bathOptions->getById($id);
        if (!$option) {
            $this->error('Položka nenalezena');
        }
        $this['bathOptionForm']->setDefaults($option->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
        $this->template->parameter = $option->parameter;
        $this->template->picture = $option->picture;
    }

    /** Smazani parametru */
    public function actionDeleteOption(int $id): void
    {
        $option = $this->orm->bathOptions->getById($id);
        if (!$option) {
            $this->error('Položka nenalezena');
        }
        if ($option->hasItems) {
            $this->error('Položku nelze smazat');
        }
        $parameterId = $option->parameter->id;
        $this->orm->bathOptions->removeAndFlush($option);
        $this->flashMessage('Položka byla odstraněna');
        $this->redirect('options', ['id' => $parameterId]);
    }

    /** Grid s parametry koupelny */
    protected function createComponentBathParameters(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->bathParameters);
        $grid->addCellsTemplate(__DIR__ . '/../templates/BathParameter/grid.cells.latte');

        $grid->addColumn('order', 'Pořadí')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('options', 'Možnosti');

        $grid->addTopAction('add', 'Přidat');
        $grid->addRowAction('options', 'Možnosti', 'list-ul');
        $grid->addRowAction('edit', 'Upravit');
        $grid->addRowAction('delete', 'Smazat')
            ->setCondition("\$hasOptions == 0");

        return $grid;
    }

    /** Grid s moznostma parametru koupelny */
    protected function createComponentBathOptions(): BaseDatagrid
    {
        $parameter = $this->orm->bathParameters->getById($this->getParameter('id'));

        $grid = $this->datagridFactory->create($this->orm->bathOptions);
        $grid->addCellsTemplate(__DIR__ . '/../templates/BathParameter/option.grid.cells.latte');
        $grid->settings->setDataSourceFilter(['parameter' => $parameter->id]);

        $grid->addColumn('order', 'Pořadí')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();

        if ($parameter->id === BathParameter::COLOR) {
            $grid->addColumn('color', 'Barva');
        }

        $grid->addColumn('description', 'Popis');

        $grid->addTopAction('addOption', 'Přidat', ['id' => $this->getParameter('id')]);
        $grid->addRowAction('editOption', 'Upravit', 'pencil');
        $grid->addRowAction('deleteOption', 'Smazat', 'trash')
            ->setCondition("\$hasItems == 0")
            ->setDialog('Potvrzení', 'Přejete si opravdu odstranit položku?');

        return $grid;
    }

    /** Formular na parametr koupelny */
    protected function createComponentBathParameterForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addInteger('order', 'Pořadí')
            ->setRequired()
            ->addRule(BaseForm::RANGE, null, [1, 100]);
        $form->addText('name', 'Název', null, 250)
            ->setRequired();
        $form->addSubmit('submit', 'Uložit');

        $form->onSuccess[] = function (array $values): void {
            if ($this->getAction() === 'add') {
                $order = $values['order'];
                $values['order'] = 101;
                $entity = $this->orm->bathParameters->insertEntity(null, $values);
                $this->orm->bathParameters->changeOrder($entity, $order);
                $this->orm->bathParameters->flush();
            } else {
                $entity = $this->orm->bathParameters->getById($this->getParameter('id'));

                if ($entity->order !== $values['order']) {
                    $this->orm->bathParameters->changeOrder($entity, $values['order']);
                    $this->orm->bathParameters->flush();
                }

                $this->orm->bathParameters->updateEntity($entity->id, null, $values);
            }

            $this->flashMessage('Položka byla uložena');
            $this->redirect('default');
        };

        return $form;
    }

    /** Formular na parametr koupelny */
    protected function createComponentBathOptionForm(): BaseForm
    {
        $id = $this->getParameter('id');
        $option = $this->getAction() === 'editOption'
            ? $this->orm->bathOptions->getById($id)
            : null;
        $parameter = $this->getAction() === 'addOption'
            ? $this->orm->bathParameters->getById($id)
            : $option->parameter;

        $form = new BaseForm();
        $form->addInteger('order', 'Pořadí')
            ->setRequired()
            ->addRule(BaseForm::RANGE, null, [1, 100]);
        $form->addText('name', 'Název', null, 250)
            ->setRequired();
        $form->addText('description', 'Popis', null, 250);

        if ($parameter->id === BathParameter::COLOR) {
            $form->addColorPicker('color', 'Barva')->setRequired();
        }

        if ($parameter->id === BathParameter::TYPE) {
            $upload = $form->addUpload('picture', 'Obrázek')->addRule(BaseForm::IMAGE);

            if ($option && $option->picture) {
                $upload->setOption('description', $option->picture->name);
            }
        }

        $form->addSubmit('submit', 'Uložit');

        $form->onSuccess[] = function (array $values): void {
            /** @var FileUpload $picture */
            $picture = $values['picture'] ?? null;
            unset($values['picture']);

            if ($this->getAction() === 'addOption') {
                $order = $values['order'];
                $values['parameter'] = $this->getParameter('id');
                $values['order'] = 101;
                $option = $this->orm->bathOptions->insertEntity(null, $values);
                $this->orm->bathOptions->changeOrder($option, $order);
                $this->orm->bathOptions->flush();
            } else {
                $option = $this->orm->bathOptions->getById($this->getParameter('id'));

                if ($option->order !== $values['order']) {
                    $this->orm->bathOptions->changeOrder($option, $values['order']);
                    $this->orm->bathOptions->flush();
                }

                $this->orm->bathOptions->updateEntity($option->id, null, $values);
            }

            if ($picture && $picture->hasFile()) {
                if ($option->picture) {
                    $this->orm->files->updateFile($option->picture, $picture);
                } else {
                    $option->picture = $this->orm->files->createFile(
                        $picture,
                        BathOptionRepository::IMAGE_DIR . "/$option->id"
                    );
                    $this->orm->bathOptions->persistAndFlush($option);
                }
            }

            $this->flashMessage('Položka byla uložena');
            $this->redirect('options', ['id' => $option->parameter->id]);
        };

        return $form;
    }
}