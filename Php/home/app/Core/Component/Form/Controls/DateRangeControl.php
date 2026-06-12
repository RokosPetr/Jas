<?php
declare(strict_types=1);

namespace App\Core\Component\Form\Controls;

use App\Core\Utils\DateRange;
use App\Core\Utils\DateTime;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;
use Nette\Forms\Helpers;
use Nette\InvalidArgumentException;
use Nette\Utils\Html;

/**
 * Form control for selecting date range.
 * Class DateRangeControl
 */
class DateRangeControl extends BaseControl
{
    protected string $format;
    protected string $jsFormat;

    protected Html $fromControl;
    protected Html $toControl;

    protected ?string $required = null;

    public const REQUIRED_FROM = 'from';
    public const REQUIRED_TO = 'to';
    public const REQUIRED_BOTH = 'both';

    public function __construct(
        ?string $caption = null,
        string $format = DateTime::CZ_DATE,
        string $jsFormat = DateTime::JS_DATE
    ) {
        parent::__construct($caption);
        $this->format = $format;
        $this->jsFormat = $jsFormat;

        $this->fromControl = Html::el('input', ['type' => 'text', 'name' => null]);
        $this->toControl = Html::el('input', ['type' => 'text', 'name' => null]);
    }

    public function getControl()
    {
        $this->setOption('rendered', true);
        $rules = clone $this->getRules();

        if ($this->isRequired() && $this->required === self::REQUIRED_TO) {
            // disable required rule for field "from" if only "to" field is required
            $rules->setRequired(false);
        }

        $from = clone $this->fromControl;
        $from->addAttributes(
            [
                'id' => $this->getHtmlId() . '-from',
                'name' => $this->getHtmlName() . '[from]',
                'required' => $rules->isRequired(),
                'disabled' => $this->isDisabled(),
                'data-nette-rules' => Helpers::exportRules($rules) ?: null,
                'dateformat' => $this->jsFormat,
                'value' => $this->getFrom(),
                'maxDate' => $this->getTo(),
            ]
        )->addClass('form-control dateRangePicker');

        $rules = clone $this->getRules();

        if ($this->isRequired() && $this->required === self::REQUIRED_FROM) {
            // disable required rule for field "to" if only "from" field is required
            $rules->setRequired(false);
        }

        $to = clone $this->toControl;
        $to->addAttributes(
            [
                'id' => $this->getHtmlId() . '-to',
                'name' => $this->getHtmlName() . '[to]',
                'required' => $rules->isRequired(),
                'disabled' => $this->isDisabled(),
                'data-nette-rules' => Helpers::exportRules($rules) ?: null,
                'dateformat' => $this->jsFormat,
                'value' => $this->getTo(),
                'minDate' => $this->getFrom(),
            ]
        )->addClass('form-control dateRangePicker');

        $container = Html::el('div', ['class' => 'input-group']);
        $container->addHtml(
            Html::el('span', ['class' => 'input-group-addon'])->addText('Od' . ' ')->addHtml(
                Html::el('i', ['class' => 'fa fa-calendar'])
            )
        );
        $container->addHtml($from);

        $container->addHtml(
            Html::el('span', ['class' => 'input-group-addon'])->addText('Do' . ' ')->addHtml(
                Html::el('i', ['class' => 'fa fa-calendar'])
            )
        );
        $container->addHtml($to);

        return $container;
    }

    public function getFromControlPrototype(): Html
    {
        return $this->fromControl;
    }

    public function getToControlPrototype(): Html
    {
        return $this->toControl;
    }

    /**
     * @return Html|string
     */
    public function getLabel($caption = null)
    {
        return parent::getLabel($caption)->for($this->getHtmlId() . '-from');
    }

    public function loadHttpData(): void
    {
        if (!$this->isDisabled()) {
            $httpFrom = $this->getHttpData(Form::DATA_TEXT, '[from]');
            $httpTo = $this->getHttpData(Form::DATA_TEXT, '[to]');
            $this->value = new DateRange(
                $httpFrom ? \DateTimeImmutable::createFromFormat($this->format, $httpFrom) : null,
                $httpTo ? \DateTimeImmutable::createFromFormat($this->format, $httpTo) : null
            );
        }
    }

    public function setValue($value): self
    {
        if (is_array($value)) {
            if (!($value['from'] instanceof \DateTimeInterface)) {
                $value['from'] = \DateTimeImmutable::createFromFormat($this->format, $value['from']) ?: null;
            }
            if (!($value['to'] instanceof \DateTimeInterface)) {
                $value['to'] = \DateTimeImmutable::createFromFormat($this->format, $value['to']) ?: null;
            }
            $this->value = new DateRange($value['from'], $value['to']);
        } elseif ($value instanceof DateRange || is_null($value)) {
            $this->value = $value;
        } else {
            throw new InvalidArgumentException(
                sprintf(
                    "Value must be array or DateRange or null, %s given in field '%s'.",
                    gettype($value),
                    $this->name
                )
            );
        }

        return $this;
    }

    /**
     * @return mixed|array
     */
    public function getRawValue()
    {
        return $this->value;
    }

    public function isFilled(): bool
    {
        if ($this->isRequired()) {
            if ($this->required == self::REQUIRED_FROM) {
                // from value must be filled
                return !is_null($this->value->getFrom());
            } elseif ($this->required == self::REQUIRED_TO) {
                // to value must be filled
                return !is_null($this->value->getTo());
            } else { // ($this->required == self::REQUIRED_BOTH) {
                // both values must be filled
                return !is_null($this->value->getFrom()) && !is_null($this->value->getTo());
            }
        } else {
            // otherwise one value is enough
            return !is_null($this->value->getFrom()) || !is_null($this->value->getTo());
        }
    }

    public function setRequired($value = self::REQUIRED_BOTH): self
    {
        $this->required = $value;
        return parent::setRequired('messages.dateRange.required.' . $value);
    }

    protected function getFrom(): ?string
    {
        return $this->value && $this->value->getFrom()
            ? $this->value->getFrom()->format($this->format)
            : null;
    }

    protected function getTo(): ?string
    {
        return $this->value && $this->value->getTo()
            ? $this->value->getTo()->format($this->format)
            : null;
    }
}
