<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Core\Utils\DateTime;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\StockModule\Component\WarehousemenTable;
use App\Modules\StockModule\Orm\WarehousemanHours\WarehousemanHour;
use App\Modules\StockModule\Orm\Warehousemen\Warehouseman;
use Contributte\PdfResponse\PdfResponse;
use Nextras\Dbal\Utils\DateTimeImmutable;

/** Presenter pro praci se skladniky */
final class WarehousemanPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Seznam skladníků',
        'edit' => 'Upravit jméno skladníka',
        'add' => 'Přidat skladníka',
        'items' => 'Položky skladníků',
        'addHours' => 'Nastavit hodiny skladníků'
    ];

    /** Uprava jmena skladnika */
    public function actionEdit(int $id): void
    {
        $warehouseMan = $this->orm->warehousemen->getById($id);
        if (!$warehouseMan) {
            $this->error('Položka nenalezena');
        }
        $this['editWarehousemanForm']->setDefaults(['name' => $warehouseMan->name]);
    }

    /** Smazani (deaktivace) skladnika */
    public function actionDelete(int $id, string $source = null): void
    {
        $warehouseMan = $this->orm->warehousemen->getById($id);
        if (!$warehouseMan || $warehouseMan->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->warehousemen->updateEntity($warehouseMan->id, null, [
            'deleted' => true,
            'deletedBy' => $this->user->id,
            'deletedAt' => new \DateTime()
        ]);
        $this->flashMessage('Skladník byl deaktivován');
        $this->redirect($source === 'table' ? 'items' :'default');
    }

    /** Zadavani pracovni doby skladnikum */
    public function actionAddHours(): void
    {
        $date = new DateTime();
        $sessionData = $this->getSession('warehousemanTable');
        $selectedMonth = $sessionData->selectedMonth ?? (int) $date->format('n');
        $selectedYear = $sessionData->selectedYear ?? (int) $date->format('Y');
        $defaults = [];

        foreach ($this->orm->warehousemanHours->loadWorkingHours($selectedMonth, $selectedYear) as $day => $hours) {
            $defaults[$day . '_' . $selectedMonth . '_' . $selectedYear] = $hours;
        }

        $this['addHoursForm']->setDefaults(['days' => $defaults]);
        $this->template->selectedYear = $selectedYear;
    }

    /** Generovani PDF s tabulkou skladniku a jejich vykonu */
    public function actionTableToPdf(): void
    {
        $this['warehousemanTable']->setPdfMode();
        $this->template->setFile(__DIR__ . '/../templates/Warehouseman/tableToPdf.latte');
        $pdf = new PdfResponse($this->template);
        $pdf->styles = file_get_contents(__DIR__ . '/../Component/templates/warehousemenTablePdf.css');
        $pdf->setSaveMode(PdfResponse::INLINE);
        $pdf->pageFormat = "A4-L";
        $this->sendResponse($pdf);
    }

    /** Datagrid se skladniky */
    protected function createComponentWarehousemen(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->warehousemen);
        $grid->addColumn('webId', 'Identifikační číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Jméno')->enableSort();
        $grid->addColumn('createdAt', 'Datum nástupu')->dateFormat(DATE);
        $grid->addColumn('deletedAt', 'Datum odchodu')->dateFormat(DATE);

        $grid->addTopAction('items', 'Položky skladníků');
        $grid->addTopAction('add', 'Přidat skladníka');
        $grid->addRowAction('edit', 'Upravit jméno skladníka')->setCondition("\$deleted == 0");
        $grid->addRowAction('delete', 'Deaktivovat skladníka')->setCondition("\$deleted == 0");

        $grid->addLegend('Již nepracuje', 'legend_red', "\$deleted == 1");

        $grid->setFilterFormFactory(function (): FilterContainer {
            $form = new FilterContainer();
            $options = [
                '' => 'Vše',
                0 => 'Aktuálně zaměstnáni',
                1 => 'Propuštění'
            ];
            $form->addSelect('deleted', 'Stav', $options)->setDefaultValue(0);
            return $form;
        });

        return $grid;
    }

    /** formular pro zadavani pracovni doby skladniku */
    protected function createComponentAddHoursForm(): BaseForm
    {
        $date = new DateTime();
        $sessionData = $this->getSession('warehousemanTable');
        $selectedMonth = $sessionData->selectedMonth ?? (int) $date->format('n');
        $selectedYear = $sessionData->selectedYear ?? (int) $date->format('Y');
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
        $date->setDate($selectedYear, $selectedMonth, 1);
        $form = new BaseForm();
        $dayContainer = $form->addContainer('days');

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dayInput = $dayContainer->addText($date->format('j_n_Y'), $date->format('j.n.'))
                ->setHtmlType('number')
                ->setHtmlAttribute('step', 0.5);
            $dayInput->addCondition(BaseForm::FILLED)
                ->addRule(BaseForm::RANGE, null, [0, 24]);

            if (!$date->isWorkingDay()) {
                $dayInput->setOption('weekend', true);
            }

            $date->modify('+1 day');
        }

        $form->addSubmit('add', 'ULOŽIT');
        $form->onSuccess[] = function (array $values): void {
            foreach ($values['days'] as $day => $hours) {
                $date = DateTime::createFromFormat('j_n_Y', $day);
                $record = $this->orm->warehousemanHours->getBy(['date' => $date->format(DateTime::DB_DATE)]);

                if ($record && !$hours) {
                    $record->getRepository()->remove($record);
                    continue;
                }

                if ($hours && !$record) {
                    $record = new WarehousemanHour();
                    $record->date = $date;
                    $record->length = $hours;
                    $this->orm->warehousemanHours->persist($record);
                    continue;
                }

                if ($record && $record->length !== floatval($hours)) {
                    $record->length = $hours;
                    $record->getRepository()->persist($record);
                }
            }
            $this->orm->warehousemanHours->flush();
            $this->flashMessage('Zadané hodiny byly uloženy');
            $this->redirect('items');
        };

        return $form;
    }

    /** Formular pro upravu jmena skladnika */
    protected function createComponentEditWarehousemanForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('name', 'Jméno', null, 250)->setRequired();
        $form->addSubmit('edit', 'Upravit');
        $form->onSuccess[] = function (BaseForm $form): void {
            $this->orm->warehousemen->updateEntity($this->getParameter('id'), $form);
            $this->flashMessage('Jméno skladníka bylo upraveno');
            $this->redirect('default');
        };
        return $form;
    }

    /** Formular pro pridani noveho skladnika */
    protected function createComponentAddWarehousemanForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addInteger('webId', 'Číslo skladníka ve výkazu (ID):')
            ->setRequired()
            ->addRule(BaseForm::RANGE, null, [1, 28]);
        $form->addText('name', 'Jméno:', null, 250)
            ->setRequired();
        $form->addDate('createdAt', 'Datum nástupu')
            ->setDefaultValue(date(DateTime::CZ_DATE))
            ->setRequired();
        $form->addSubmit('add', 'PŘIDAT');

        $form->onSuccess[] = function (array $values): void {
            $createdAt = DateTimeImmutable::createFromFormat(DateTime::CZ_DATE, $values['createdAt'])->setTime(0, 0);
            $this->orm->warehousemen->beginTransaction();
            $warehouseman = $this->orm->warehousemen->getBy(['webId' => $values['webId'], 'deleted' => false]);

            if ($warehouseman) {
                $deleteParams = [
                    'deleted' => true,
                    'deletedBy' => $this->user->id,
                    'deletedAt' => $createdAt->modify('-1 minute')
                ];
                $this->orm->warehousemen->updateEntity($warehouseman->id, null, $deleteParams);
            }

            $warehouseman = new Warehouseman();
            $warehouseman->webId = $values['webId'];
            $warehouseman->name = $values['name'];
            $warehouseman->createdBy = $this->getSysUser();
            $warehouseman->createdAt = $createdAt;
            $this->orm->warehousemen->persistAndFlush($warehouseman);
            $this->orm->warehousemen->commitTransaction();
            $this->flashMessage('Skladník byl přidán');
            $this->redirect('items');
        };

        return $form;
    }

    /** Tabulka se skladniky a jejich vykony */
    protected function createComponentWarehousemanTable(): WarehousemenTable
    {
        return new WarehousemenTable($this->orm);
    }
}
