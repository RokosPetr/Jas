<?php
declare(strict_types = 1);

namespace App\Core\Component\Form;

use App\Core\Component\Form\Controls\DateRangeControl;
use App\Core\Component\Form\Controls\DateTimeInput;
use App\Core\Utils\DateTime;
use Nette\Forms\Form;
use Nette\Forms\Controls\TextInput;
use App\Core\Component\Form\Controls\HiddenControl;
use Nette\Forms\Controls\HiddenField;

trait FormTrait
{
    public function addDate(
        string $name,
        string $label,
        string $format = DateTime::CZ_DATE,
        string $jsDateFormat = DateTime::JS_DATE
    ): DateTimeInput {

        return $this[$name] = $this->getBaseDateTimeInput($label, 'datepicker', $format, $jsDateFormat);
    }

    public function addDateTime(
        string $name,
        string $label,
        string $format = DateTime::CZ_DATETIME,
        string $jsDateFormat = DateTime::JS_DATE,
        string $jsTimeFormat = DateTime::JS_TIME
    ): DateTimeInput {

        return $this[$name] = $this->getBaseDateTimeInput($label, 'datetimepicker', $format, $jsDateFormat, $jsTimeFormat);
    }

    public function addTime(
        string $name,
        string $label,
        string $format = DateTime::CZ_TIME,
        string $jsTimeFormat = DateTime::JS_TIME
    ): DateTimeInput {

        return $this[$name] = $this->getBaseDateTimeInput($label, 'timepicker', $format, null, $jsTimeFormat);
    }

    private function getBaseDateTimeInput(
        string $label,
        string $type,
        string $format,
        string $jsDateFormat = null,
        string $jsTimeFormat = null
    ): DateTimeInput {

        $dateTimeInput = new DateTimeInput($label);
        $controlPrototype = $dateTimeInput->getControlPrototype()
            ->addClass($type)
            ->addClass('dateFormat')
            ->addClass('no-focus')
            ->setAttribute('dateformat', $format)
            ->setAttribute('autocomplete', 'off');

        if ($jsDateFormat) {
            $controlPrototype->setAttribute('jsdateformat', $jsDateFormat);
        }

        if ($jsTimeFormat) {
            $controlPrototype->setAttribute('jstimeformat', $jsTimeFormat);
        }

        $dateTimeInput->addCondition(Form::FILLED)->addRule(
            static fn() : bool => \DateTime::createFromFormat($format, $dateTimeInput->getValue()) !== false,
            'Neplatný formát'
        );

        return $dateTimeInput;
    }

    public function addDateRange(string $name, ?string $caption): DateRangeControl
    {
        $input = new DateRangeControl($caption);
        $this[$name] = $input;
        return $input;
    }

    public function addText($name, $label = null, int $cols = null, int $maxLength = null): TextInput
    {
        $control = parent::addText($name, $label, $cols, $maxLength);

        if ($maxLength) {
            $control->addRule($this::MAX_LENGTH, null, $maxLength);
        }

        return $control;
    }

    public function addPassword($name, $label = null, int $cols = null, int $maxLength = null): TextInput
    {
        $control = parent::addPassword($name, $label, $cols, $maxLength);

        if ($maxLength) {
            $control->addRule($this::MAX_LENGTH, null, $maxLength);
        }

        return $control;
    }

    public function addHidden(string $name, $default = null): HiddenField
    {
        return $this[$name] = (new HiddenControl())->setDefaultValue($default);
    }

    public function addColorPicker(string $name, $label = null): TextInput
    {
        $control = parent::addText($name, $label);
        $control->addRule(Form::PATTERN_ICASE, 'Nevalidní hex pro barvu', '^#[0-9A-F]{6}$');
        $control->getControlPrototype()
            ->addClass('color-picker')
            ->setAttribute('autocomplete', 'off');
        return $control;
    }
}
