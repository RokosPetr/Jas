<?php
declare(strict_types = 1);

namespace App\Core\Component\Form;

use Nette\Application\UI\Form;
use Nette\Forms\ControlGroup;
use Nette\Forms\Controls\TextInput;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Controls\BaseControl;
use Nette\InvalidArgumentException;
use Nette\Utils\Html;

class BaseForm extends Form
{
    use FormTrait;

    /** @var ControlGroup[] $tabs */
    private array $tabs = [];
    protected array $combinedElements = [];

    public function __construct(IContainer $parent = null, string $name = null) {
        parent::__construct($parent, $name);
        $this->setRenderer(new FormRenderer($this));
        $this->addProtection('Vypršel bezpečnostní časový limit.');
    }

    public function setPredictor(string $name, int $minLength = 3, int $numberOfLines = 10) : Html
    {
        $component = $this->getComponent($name);

        if (is_a($component, TextInput::class)) {
            return $component->getControlPrototype()
                ->addClass("hasPredictor")
                ->setAttribute('data-min-length', $minLength)
                ->setAttribute('data-number-of-lines', $numberOfLines)
                ->setAttribute('data-name', lcfirst($this->getName()))
                ->setAttribute('autocomplete', 'off');
        }
        throw new \Exception("Component '$name' is not TextInput");
    }

    protected function removeProtection(): void
    {
        unset($this[self::PROTECTOR_ID]);
    }

    /**
     * Adds unique form value check to specified form controls
     * @param BaseControl[] $controls
     */
    protected function addUniqueGroupValueRule(array $controls): void
    {
        foreach ($controls as $index => $input) {
            $checkValues = $controls;
            unset($checkValues[$index]);
            $input->addRule(self::NOT_EQUAL, 'form_value_exists', $checkValues);
        }
    }

    public function addTab(string $tabId, string $caption, bool $selected = false, string $faIcon = null): ControlGroup
    {
        $group = new ControlGroup();
        $group->setOption('tabId', $tabId);
        $group->setOption('label', $caption);
        $group->setOption('selected', $selected);
        $group->setOption('visual', true);

        if ($faIcon) {
            $group->setOption('icon', $faIcon);
        }
        $this->setCurrentGroup($group);

        if (!is_scalar($caption) || isset($this->tabs[$caption])) {
            return $this->tabs[] = $group;
        }

        return $this->tabs[$tabId] = $group;
    }

    public function removeTab($tabId): void
    {
        if (is_string($tabId) && isset($this->tabs[$tabId])) {
            $group = $this->tabs[$tabId];
        } elseif ($tabId instanceof ControlGroup && in_array($tabId, $this->tabs, true)) {
            $group = $tabId;
            $tabId = array_search($group, $this->tabs, true);
        } else {
            throw new InvalidArgumentException("Tab not found in form '$this->name'");
        }

        foreach ($group->getControls() as $control) {
            $control->getParent()->removeComponent($control);
        }

        unset($this->tabs[$tabId]);
    }

    public function getTabs() : array
    {
        return $this->tabs;
    }

    public function getTab($tabId): ?ControlGroup
    {
        return $this->tabs[$tabId] ?? null;
    }

    public function setCombinedElements(string $label, BaseControl $element1, BaseControl $element2): void
    {
        $this->combinedElements[$element1->name . '_' . $element2->name] = [
            'label' => $label,
            $element1->name => $element1,
            $element2->name => $element2
        ];
    }

    public function getCombinedElements(): array
    {
        return $this->combinedElements;
    }
}
