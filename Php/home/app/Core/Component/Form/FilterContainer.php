<?php
declare(strict_types = 1);

namespace App\Core\Component\Form;

use App\Core\Utils\DateTime;
use Nette\Forms\Container;
use Nette\Forms\Controls\TextInput;

class FilterContainer extends Container
{
    use FormTrait;

    public string $caption;
    protected bool $isLabelHidden = false;

    public function addContainer($name): self
    {
        $container = new self();
        $container->currentGroup = $this->currentGroup;

        if ($this->currentGroup !== null) {
            $this->currentGroup->add($container);
        }

        return $this[$name] = $container;
    }

    public function setContainerCaption(string $caption): void
    {
        $this->caption = $caption;
    }

    public function addDateFrom(
        string $name,
        string $placeholder,
        string $format = DateTime::CZ_DATE,
        string $jsDateFormat = DateTime::JS_DATE
    ): TextInput {

        return $this[$name]->addDate("_DateFrom", $placeholder, $format, $jsDateFormat)
            ->setAttribute('placeholder', $placeholder);
    }

    public function addDateTo(
        string $name,
        string $placeholder,
        string $format = DateTime::CZ_DATE,
        string $jsDateFormat = DateTime::JS_DATE
    ): TextInput {

        return $this[$name]->addDate("_DateTo", $placeholder, $format, $jsDateFormat)
            ->setAttribute('placeholder', $placeholder);
    }

    public function addDateTimeFrom(
        string $name,
        string $placeholder,
        string $format = DateTime::CZ_DATETIME,
        string $jsDateFormat = DateTime::JS_DATE,
        string $jsTimeFormat = DateTime::JS_TIME
    ): TextInput {

        return $this[$name]->addDateTime("_DateTimeFrom", $placeholder, $format, $jsDateFormat, $jsTimeFormat)
            ->setAttribute('placeholder', $placeholder);
    }

    public function addDateTimeTo(
        string $name,
        string $placeholder,
        string $format = DateTime::CZ_DATETIME,
        string $jsDateFormat = DateTime::JS_DATE,
        string $jsTimeFormat = DateTime::JS_TIME
    ): TextInput {

        return $this[$name]->addDateTime("_DateTimeTo", $placeholder, $format, $jsDateFormat, $jsTimeFormat)
            ->setAttribute('placeholder', $placeholder);
    }

    public function addTextFrom(string $name, string $placeholder): TextInput
    {
        return $this[$name]->addText("_TextFrom", $placeholder)
            ->setAttribute('placeholder', $placeholder);
    }

    public function addTextTo(string $name, string $placeholder): TextInput
    {
        return $this[$name]->addText("_TextTo", $placeholder)
            ->setAttribute('placeholder', $placeholder);
    }

    public function addIntegerFrom(string $name, string $placeholder): TextInput
    {
        return $this[$name]->addInteger("_IntegerFrom", $placeholder)
            ->setAttribute('placeholder', $placeholder);
    }

    public function addIntegerTo(string $name, string $placeholder): TextInput
    {
        return $this[$name]->addInteger("_IntegerTo", $placeholder)
            ->setAttribute('placeholder', $placeholder);
    }

    public function isLabelHidden(): bool
    {
        return $this->isLabelHidden;
    }

    public function hideLabel(): self
    {
        $this->isLabelHidden = true;
        return $this;
    }
}
