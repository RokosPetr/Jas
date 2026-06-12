<?php
declare(strict_types = 1);

namespace App\Core\Utils;

class DateRange
{
    protected ?\DateTimeInterface $from;
    protected ?\DateTimeInterface $to;

    public function __construct(?\DateTimeInterface $from, ?\DateTimeInterface $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function getFrom() : ?\DateTimeInterface
    {
        return $this->from;
    }

    public function getTo() : ?\DateTimeInterface
    {
        return $this->to;
    }
}
