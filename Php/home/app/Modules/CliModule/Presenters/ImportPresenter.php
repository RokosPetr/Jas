<?php
declare(strict_types=1);

namespace App\Modules\CliModule\Presenters;

use App\Core\Component\Form\BaseForm;
use App\Modules\CliModule\Service\DeliveryServiceImporter;
use App\Modules\CliModule\Service\MovementImporter;
use App\Modules\CliModule\Service\WarehouseImporter;
use App\Modules\Presenters\BasePresenter;
use Nette\Http\FileUpload;
use Nette\Neon\Neon;

/** Presenter pro import dat */
final class ImportPresenter extends BasePresenter
{
    public array $titles = [
        'default' => 'Seznam importů',
        'movements' => 'Importy pohybů',
        'warehousemanItems' => 'Import položek skladníků',
        'stockItems' => 'Import skladových položek',
        'deliveryItems' => 'Import vyvezených dokladů',
        'noteSumData' => 'Import součtů dokladu'
    ];

    /** @inject */
    public WarehouseImporter $warehouseImporter;

    /** @inject */
    public MovementImporter $movementImporter;

    /** @inject */
    public DeliveryServiceImporter $deliveryServiceImporter;

    /** Seznam dostupnych importu spoustenych CRONem */
    public function renderDefault(): void
    {
        $this->template->stores = $this->orm->stores->findAll()->fetchPairs('id', 'name');
        $this->template->importDates = $this->orm->imports->findAll()->fetchPairs('name', 'date');
    }

    /** Seznam dostupnych importu pohybu spoustenych CRONem */
    public function renderMovements(): void
    {
        $this->template->stores = $this->orm->stores->findAll()->fetchPairs('id', 'name');
        $this->template->importDates = $this->orm->imports->findAll()->fetchPairs('name', 'date');
    }

    /** Import zabezpecenych zdroju z konfiguracniho souboru */
    public function actionResources(): void
    {
        $resourcesConfig = Neon::decode(file_get_contents(CONFIG_DIR . '/resources.neon'));
        $localResources = $this->orm->resources->findAll()->fetchPairs('link');

        foreach ($resourcesConfig as $module => $moduleConfig) {
            foreach ($moduleConfig['presenters'] as $presenter => $presenterConfig) {
                foreach ($presenterConfig['actions'] as $action => $description) {
                    $resourceLink = ":$module:$presenter:$action";

                    if (isset($localResources[$resourceLink])) {
                        unset($localResources[$resourceLink]);
                        continue;
                    }

                    $this->orm->resources->insertEntity(null, [
                        'link' => $resourceLink,
                        'description' => $description
                    ]);
                }
            }
        }

        foreach ($localResources as $resource) {
            $this->orm->resources->removeAndFlush($resource);
        }

        $this->flashMessage('Import zabezpecenych zdroju proběhl v pořádku');
        $this->redirect('default');
    }

    /** Import polozek (vykon) skladniku */
    public function actionWarehousemanItems(): void
    {
        $this->setView('result');
        $result = $this->warehouseImporter->importWarehousemanItems();
        if (!empty($result->error)) {
            $this->template->result = $result->error;
        } else {
            $newItemsCount = $result->importCount - $result->deletedCount;
            $this->template->result = "Bylo smazáno $result->deletedCount položek, naimportováno $result->importCount "
                . "(rozdíl $newItemsCount položek).";
        }

    }

    /** Import skladovych zasob zvolene pobocky */
    public function actionStockItems(int $id): void
    {
        $this->setView('result');
        $this->template->result = $this->warehouseImporter->importStockItems($id);
    }
    /** Import skladovych zasob zvolene pobocky druhá část*/
    public function actionStockItemsSecond(int $id): void
    {
        $this->setView('result');
        $this->template->result = $this->warehouseImporter->importStockItems($id, true);
    }

    /** Import skladovych zasob zvolene pobocky */
    public function actionPartners(int $id): void
    {
        $this->setView('result');
        $this->template->result = $this->warehouseImporter->importPartners($id);
    }

    /** Import rabatovych listu */
    public function actionDiscounts(bool $items = false): void
    {
        $this->setView('result');
        $this->template->result = $items
            ? $this->warehouseImporter->importItemDiscounts()
            : $this->warehouseImporter->importGroupDiscounts();
    }

    /** Import pohybu aktualniho roku zvolene pobocky */
    public function actionCurrentMovements(int $id): void
    {
        $this->setView('result');
        $this->template->result = $this->movementImporter->importCurrentMovements($id);
    }

    /** Parovani prevodek */
    public function actionTransfers(): void
    {
        $this->setView('result');
        $this->template->result = $this->movementImporter->findTransferParents();
    }

    /** Form pro spousteni importu sluzeb jako polozek dodacich listu */
    public function createComponentServicesImportForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addSelect('store', 'Pobočka', $this->orm->stores->findOrderedStoreNames())->setRequired();
        $form->addSubmit('import', 'Spoustit import služeb');

        $form->onSuccess[] = function (array $values): void {
            $result = $this->deliveryServiceImporter->importNoteServices($values['store']);
            $this->flashMessage($result);
            $this->redirect('movements');
        };

        return $form;
    }

    /** Form pro spousteni importu chybejicich hlavice dokladu ke sluzbam */
    public function createComponentServiceNotesImportForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addSelect('store', 'Pobočka', $this->orm->stores->findOrderedStoreNames())->setRequired();
        $form->addSubmit('import', 'Spoustit import dokladů');

        $form->onSuccess[] = function (array $values): void {
            $result = $this->deliveryServiceImporter->importMissingNotes($values['store']);
            $this->flashMessage($result);
            $this->redirect('movements');
        };

        return $form;
    }

    /** Form pro update dokladu */
    public function createComponentDeliveryNotesDataImportForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addSelect('store', 'Pobočka', $this->orm->stores->findAll()->fetchPairs('id', 'name'))
            ->setRequired();
        $form->addUpload('noteItems', 'Soubor')
            ->setRequired()
            ->addRule(BaseForm::PATTERN, 'Pouze csv soubor', '.*\.csv$');
        $form->addSubmit('import', 'Importovat');

        $form->onSuccess[] = function (array $values): void {
            /** @var FileUpload $fileUpload */
            $fileUpload = $values['noteItems'];
            $count = $this->movementImporter->updateDeliveryNoteData(
                $values['store'],
                $fileUpload->contents
            );
            $this->flashMessage("Import souboru $fileUpload->name proběhl úspěšně: $count updates!");
            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentDeliveryItemImportForm(): BaseForm
    {
        $years = range((int) date('Y'), 2020, -1);
        $form = new BaseForm();
        $form->addSelect('store', 'Pobočka', $this->orm->stores->findAll()->fetchPairs('id', 'name'))
            ->setRequired();
        $form->addSelect('year', 'Rok', array_combine($years, $years))
            ->setRequired();
        $form->addUpload('deliveryItems', 'Soubor')
            ->setRequired()
            ->addRule(BaseForm::PATTERN, 'Pouze csv soubor', '.*\.csv$');
        $form->addSubmit('import', 'Importovat');

        $form->onSuccess[] = function (array $vales): void {
            $this->movementImporter->importDispatchedDeliveryItems(
                $vales['store'],
                $vales['year'],
                $vales['deliveryItems']->contents
            );
            $this->flashMessage('Import proběhl úspěšně');
            $this->redirect('movements');
        };

        return $form;
    }
}
