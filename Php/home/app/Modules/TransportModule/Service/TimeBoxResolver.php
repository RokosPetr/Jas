<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Service;

use App\Modules\TransportModule\Orm\Transports\StoreTransport;
use App\Modules\TransportModule\Service\Entity\TimeBox;

class TimeBoxResolver
{
    private array $intervals = [];
    private array $startTimes = [];
    private array $endTimes = [];
    private array $blockedTimes = [];

    public function __construct(array $transports)
    {
        /** @var StoreTransport $transport */
        foreach ($transports as $transport) {
            if ($transport->isRedundant()) {
                continue;
            }

            $this->intervals[$transport->timeFrom] = $transport->timeTill;
            $this->startTimes[$transport->timeFrom] = $transport;
            $this->endTimes[$transport->timeTill] = $transport;

            if ($transport->isLocked && !$transport->isSelfLocked) {
                $this->blockedTimes[$transport->id] = $transport;
            }
        }
    }

    public function getTimeBox(float $timeFrom, float $timeTill): TimeBox
    {
        $timeBox = new TimeBox($timeFrom, $timeTill);
        if (isset($this->startTimes[$timeFrom])) {
            $timeBox->isStartBox = true;
            $timeBox->transport = $this->startTimes[$timeFrom];
        }
        if (isset($this->endTimes[$timeTill])) {
            $timeBox->isEndBox = true;
            $timeBox->transport = $this->endTimes[$timeTill];
        }
        if (!$timeBox->transport) {
            foreach ($this->intervals as $intervalStart => $intervalEnd) {
                if ($intervalStart < $timeFrom && $intervalEnd > $timeTill) {
                    $timeBox->transport = $this->startTimes[$intervalStart];
                    break;
                }
            }
        }
        if ($timeBox->transport && isset($this->blockedTimes[$timeBox->transport->id])) {
            $timeBox->isBlocked = true;
        }
        return $timeBox;
    }
}