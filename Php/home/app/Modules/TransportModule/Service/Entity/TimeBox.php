<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Service\Entity;

use App\Modules\TransportModule\Orm\Transports\StoreTransport;

class TimeBox
{
    public ?StoreTransport $transport = null;
    public float $timeFrom;
    public float $timeTill;
    public bool $isStartBox = false;
    public bool $isEndBox = false;
    public bool $isBlocked = false;
    private ?string $class = null;

    public function __construct(float $timeFrom, float $timeTill)
    {
        $this->timeFrom = $timeFrom;
        $this->timeTill = $timeTill;
    }

    public function hasTransport(): bool
    {
        if (!$this->transport) {
            return false;
        }
        return !($this->transport->isLocked && $this->transport->isSelfLocked && $this->transport->type === StoreTransport::TYPE_TRANSPORT && !$this->transport->targets->count());
    }

    public function isEditable(): bool
    {
        return $this->hasTransport() && !$this->isBlocked;
    }

    public function getClass(): string
    {
        if (!is_null($this->class)) {
            return $this->class;
        }
        if ($this->isBlocked) {
            $this->class = 'time-box-blocked';
            return $this->class;
        }
        if (!$this->hasTransport()) {
            $this->class = '';
            return $this->class;
        }
        if ($this->transport->type === StoreTransport::TYPE_UNAVAILABILITY) {
            $this->class = 'time-box-unavailable';
            return $this->class;
        }
        if ($this->transport->errors) {
            $this->class = 'time-box-invalid';
        } else {
            $this->class = 'time-box-valid';
        }
        return $this->class;
    }
}
