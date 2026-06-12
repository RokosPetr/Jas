<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Component;

use App\Core\Utils\DateTime;
use App\Service\OrmModel;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Http\SessionSection;

/** Komponenta tabulky se skladniky a jejich vykony za dany mesic/rok */
class WarehousemenTable extends Control
{
    public const DAYS_VIEW = 1;
    public const MONTHS_VIEW = 2;

    private SessionSection $session;
    private OrmModel $orm;

    public int $selectedMonth;
    public int $selectedYear;
    public int $selectedDay;
    public int $daysCount;
    public int $viewMode;
    public bool $showAll;
    public array $shownRows;
    public bool $selectAll;
    public array $selectedRows;
    public bool $pdfMode = false;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
        $this->monitor(Presenter::class, function (): void {
            $this->session = $this->getPresenter()->getSession('warehousemanTable');
        });
    }

    public function loadState(array $params): void
    {
        $today = new DateTime();
        $this->viewMode = $this->session->viewMode ?? self::DAYS_VIEW;
        $this->selectedMonth = $this->session->selectedMonth ?? (int) $today->format('n');
        $this->selectedYear = $this->session->selectedYear ?? (int) $today->format('Y');
        $this->selectedDay = $this->session->selectedDay ?? 0;
        $this->showAll = $this->session->showAll ?? true;
        $this->shownRows = $this->session->shownRows ?? [];
        $this->selectAll = $this->session->selectAll ?? false;
        $this->selectedRows = $this->session->selectedRows ?? [];
        $this->setDaysCount();
    }

    public function setPdfMode(): self
    {
        $this->pdfMode = true;
        return $this;
    }

    public function render(): void
    {
        if ($this->viewMode === self::DAYS_VIEW) {
            $this->template->workingHours = $this->orm->warehousemanHours->loadWorkingHours(
                $this->selectedMonth,
                $this->selectedYear
            );
            $this->template->monthData = $this->orm->warehousemanItems->loadMonthData(
                $this->selectedMonth,
                $this->selectedYear
            );
        } else {
            $this->template->yearData = $this->orm->warehousemanItems->loadYearData(
                $this->selectedYear,
                $this->selectedMonth
            );
        }

        $this->template->setFile(__DIR__ . '/templates/warehousemenTable.latte');
        $this->template->render();
    }

    public function handleSetState(): void
    {
        $stateParam = $this->getPresenter()->getParameter('stateParam');
        $value = $this->getPresenter()->getParameter('value');

        $this->selectedDay = $this->session->selectedDay = 0;
        $this->$stateParam = $this->session->$stateParam = (int) $value;

        $this->setDaysCount();
        $this->redrawControl('table');
    }

    public function handleSetShowAll(): void
    {
        $value = (bool) $this->getPresenter()->getParameter('value');
        $this->showAll = $this->session->showAll = $value;
        if ($value) {
            $this->shownRows = $this->session->shownRows = [];
        } else {
            $this->shownRows = $this->session->shownRows = $this->selectedRows;
        }
        $this->redrawControl('table');
    }

    public function handleSetSelected(): void
    {
        $webId = $this->getPresenter()->getParameter('webId');
        $isChecked = (bool) $this->getPresenter()->getParameter('isChecked');

        if (is_array($webId) || is_null($webId)) {
            $selectedRows = $isChecked ? (array) $webId : [];
            $this->selectedRows = $this->session->selectedRows = array_combine($selectedRows, $selectedRows);
            $this->selectAll = $this->session->selectAll = $isChecked;
        } else {
            if ($isChecked) {
                $this->selectedRows[$webId] = $webId;
            } else {
                unset($this->selectedRows[$webId]);
            }
            $this->session->selectedRows = $this->selectedRows;
            $this->selectAll = $this->session->selectAll = false;
        }
    }

    private function setDaysCount(): void
    {
        $this->daysCount = cal_days_in_month(CAL_GREGORIAN, $this->selectedMonth, $this->selectedYear);
    }
}