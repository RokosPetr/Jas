<?php
declare(strict_types = 1);

namespace App\Core\Component\Form\Controls;

use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;

class DateTimeInput extends TextInput
{
    public function setValue($value): self
    {
        if ($value instanceof \DateTimeInterface) {
            $format = $this->getControlPrototype()->getAttribute('dateformat');
            parent::setValue($value->format($format));
        } else {
            parent::setValue($value);
        }

        return $this;
    }

    public function setMaxDate(\DateTimeInterface $maxDate = null, string $errMessage = 'date_in_past'): self
    {
        if (!$maxDate) {
            $maxDate = new \DateTime();
        }

        $this->getControlPrototype()->setAttribute('maxDate', $maxDate->getTimestamp());

        $this->addCondition(Form::FILLED)->addRule(
            function () use ($maxDate) : bool {
                $format = $this->getControlPrototype()->getAttribute('dateformat');
                $value = \DateTime::createFromFormat($format, $this->getValue());
                return $value ? $value <= $maxDate : true;
            },
            $errMessage
        );

        return $this;
    }

    public function setMinDate(\DateTimeInterface $minDate = null, string $errMessage = 'date_in_future'): self
    {
        if (!$minDate) {
            $minDate = new \DateTime();
        }

        $this->getControlPrototype()->setAttribute('minDate', $minDate->getTimestamp());

        $this->addCondition(Form::FILLED)->addRule(
            function () use ($minDate) : bool {
                $format = $this->getControlPrototype()->getAttribute('dateformat');
                $value = \DateTime::createFromFormat($format, $this->getValue());
                return $value ? $value >= $minDate : true;
            },
            $errMessage
        );

        return $this;
    }
}
