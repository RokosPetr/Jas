<?php declare(strict_types = 1);

namespace App\Core\Component\Form;

use Contributte\FormMultiplier\Buttons\RemoveButton;
use Contributte\FormMultiplier\ComponentResolver;
use Nette\Forms\Container;
use Nette\Forms\Control;
use Nette\Forms\Controls\BaseControl;

class Multiplier extends \Contributte\FormMultiplier\Multiplier
{
    public static function register(string $name = 'addMultiplier'): void
    {
        Container::extensionMethod($name, function (Container $form, $name, $factory, $copyNumber = 1, $maxCopies = null) {
            $multiplier = new self($factory, $copyNumber, $maxCopies);
            $multiplier->setCurrentGroup($form->getCurrentGroup());

            return $form[$name] = $multiplier;
        });
    }

    public function createCopies(): void
    {
        if ($this->created === true) {
            return;
        }

        $this->created = true;

        $resolver = new ComponentResolver($this->httpData, $this->values, $this->maxCopies, $this->minCopies);

        $this->attachCreateButtons();
        $this->createComponents($resolver);
        $this->detachCreateButtons();

        if ($this->maxCopies === null || $this->totalCopies < $this->maxCopies) {
            $this->attachCreateButtons();
        }

        $form = $this->getForm(false);
        if ($form !== null && $resolver->isRemoveAction() && $this->totalCopies >= $this->minCopies && !$resolver->reachedMinLimit()) {
            /** @var RemoveButton $removeButton */
            $removeButton = $this->removeButton;
            $form->setSubmittedBy($removeButton->create($this));

            $this->resetFormEvents();

            $this->onRemoveEvent();
        }

        // onCreateEvent
        $this->onCreateEvent();
    }

    public function setValues($values, bool $erase = false): self
    {
        if ($values instanceof \Traversable) {
            $values = iterator_to_array($values);
        } else {
            $values = (array) $values;
        }

        $this->values = $values;
        $this->erase = $erase;

        if ($this->created) {
            foreach ($this->getContainers() as $container) {
                $this->removeComponent($container);
                $this->totalCopies--;
            }

            $this->created = false;
            $this->detachCreateButtons();
            $this->createCopies();
        }

        return $this;
    }

    private function createComponents(ComponentResolver $resolver): void
    {
        $containers = [];
        $containerDefaults = $this->createContainer()->getValues('array');

        // Components from httpData
        if ($this->isFormSubmitted()) {
            foreach ($resolver->getValues() as $number => $_) {
                $containers[] = $container = $this->addCopy($number);

                /** @var BaseControl $control */
                foreach ($container->getComponents(false, Control::class) as $control) {
                    $control->loadHttpData();
                }
            }
        } else { // Components from default values
            foreach ($resolver->getDefaults() as $number => $values) {
                $containers[] = $this->addCopy($number, $values);
            }
        }

        // Default number of copies
        if (!$this->values) {
            $copyNumber = $this->copyNumber;
            while ($copyNumber > 0 && $this->isValidMaxCopies() && $this->totalCopies < $this->minCopies) {
                $containers[] = $container = $this->addCopy();
                $container->setValues($containerDefaults);
                $copyNumber--;
            }
        }

        // Dynamic
        foreach ($this->onCreateComponents as $callback) {
            $callback($this);
        }

        // New containers, if create button hitted
        $form = $this->getForm(false);
        if ($form !== null && $resolver->isCreateAction() && $form->isValid()) {
            $count = $resolver->getCreateNum();
            while ($count > 0 && $this->isValidMaxCopies()) {
                $this->noValidate[] = $containers[] = $container = $this->addCopy();
                $container->setValues($containerDefaults);
                $count--;
            }
        }

        if ($this->removeButton && $this->totalCopies <= $this->minCopies) {
            foreach ($containers as $container) {
                $this->detachRemoveButton($container);
            }
        }
    }

    private function attachCreateButtons(): void
    {
        foreach ($this->createButtons as $button) {
            $this->addComponent($button->create($this), $button->getComponentName());
        }
    }

    private function detachCreateButtons(): void
    {
        foreach ($this->createButtons as $button) {
            $this->removeComponentProperly($this->getComponent($button->getComponentName()));
        }
    }

    private function detachRemoveButton(Container $container): void
    {
        $button = $container->getComponent(self::SUBMIT_REMOVE_NAME);
        if ($this->getCurrentGroup() !== null) {
            $this->getCurrentGroup()->remove($button);
        }

        $container->removeComponent($button);
    }
}
