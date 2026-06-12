<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Component;

use App\Core\Utils\DateTime;
use App\Modules\TransportModule\Service\Entity\TimeBox;
use App\Modules\TransportModule\Service\TimeBoxResolver;
use App\Service\OrmModel;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Http\SessionSection;

class StoreTransportCalendar extends Control
{
    public const START_TIME = 6;
    public const END_TIME = 20;
    public const TRANSPORT_SPAN = 1;
    public const MAX_TRANSPORT_DAY = 6;

    private SessionSection $session;
    private OrmModel $orm;
    private int $storeId;
    public DateTime $date;
    /** @var TimeBoxResolver[] $timeBoxResolvers */
    private array $timeBoxResolvers = [];

    public function __construct(OrmModel $orm, int $storeId)
    {
        $this->orm = $orm;
        $this->storeId = $storeId;

        $this->monitor(Presenter::class, function (): void {
            $this->session = $this->getPresenter()->getSession('storeTransportCalendar');
        });
    }

    public function loadState(array $params): void
    {
        $this->params = $params;
        $date = !empty($this->session->date)
            ? DateTime::createFromFormat(DateTime::CZ_DATE, $this->session->date)
            : new DateTime();
        $this->setDate($date);
    }

    public function handleRefresh(): void
    {
        $this->redrawControl('storeTransportCalendar');
    }

    public function handleModifyWeek(int $value): void
    {
        $this->date->modify("$value weeks");
        $this->session->date = $this->date->format(DateTime::CZ_DATE);
        $this->redrawControl('storeTransportCalendar');
    }

    public function handleSetDate(): void
    {
        $date = DateTime::createFromFormat(DateTime::CZ_DATE, $this->getPresenter()->getParameter('date'));
        if ($date) {
            $this->setDate($date);
            $this->session->date = $this->date->format(DateTime::CZ_DATE);
        }
        $this->redrawControl('storeTransportCalendar');
    }

    public function render(): void
    {
        $transportCars = $this->orm->storeCars->findBy(['stores->id' => $this->storeId, 'deleted' => false])->fetchAll();
        $transportStores = [$this->storeId => $this->orm->stores->getById($this->storeId)->name];

        $user = $this->getPresenter()->getUser();

        if ($user->isAllowed(':Transport:StoreTransport:foreignDeliveryNotes')) {
            $transportStores[9] = "Ostrava - Michálkovice";
            $transportStores[10] = "Hlučín";
        }

        foreach ($transportCars as $car) {
            $date = clone $this->date;

            for ($j = 1; $j <= self::MAX_TRANSPORT_DAY; $j++) {
                $carTransports = $this->orm->storeTransports->findBy([
                    'deleted' => false,
                    'car->id' => $car->id,
                    'date' => $date->format(DateTime::DB_DATE)
                ])->fetchAll();
                $this->timeBoxResolvers[$car->id][$date->format(DateTime::DB_DATE)] = new TimeBoxResolver($carTransports);
                $date->modify('+1 day');
            }

            foreach ($car->stores->toCollection()->findBy(['id!=' => $this->storeId]) as $store) {
                if (!isset($transportStores[$store->id])) {
                    $transportStores[$store->id] = $store->name;
                }
            }
        }

        $this->template->cars = $transportCars;
        $this->template->transportStores = $transportStores;
        $this->template->setFile(__DIR__ . '/templates/storeTransportCalendar.latte');
        $this->template->render();
    }

    public function getTimeBox(int $car, DateTime $date, float $timeFrom, float $timeTill): TimeBox
    {
        return $this->timeBoxResolvers[$car][$date->format(DateTime::DB_DATE)]->getTimeBox($timeFrom, $timeTill);
    }

    private function setDate(DateTime $date): void
    {
        while ($date->format('N') !== '1') {
            $date->modify('-1 day');
        }
        $this->date = $date;
    }

    public static function getTimeOption(): array
    {
        $timeOption = [];

        for ($i = self::START_TIME; $i <= self::END_TIME; $i += self::TRANSPORT_SPAN) {
            $timeOption[$i] = floatToHour($i);
        }

        return $timeOption;
    }
}