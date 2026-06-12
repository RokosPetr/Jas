<?php
declare(strict_types=1);

namespace App\Core\Component\Form;

use Nette\Forms\Container;
use Nette\Forms\Control;
use Nette\Forms\ControlGroup;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\CheckboxList;
use Nette\Forms\Controls\HiddenField;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\Controls\RadioList;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Rendering\DefaultFormRenderer;
use Nette\Forms\Controls\Button;
use Nette\Forms\Form;
use Nette\HtmlStringable;
use Nette\InvalidArgumentException;
use Nette\Utils\Html;
use Nette\Forms\Controls\BaseControl;

class FormRenderer extends DefaultFormRenderer
{
    public function __construct(Form $assignedToForm = null)
    {
        $this->wrappers['controls']['container'] = '';
        $this->wrappers['controls']['subcontainer'] = '';
        $this->wrappers['pair']['container'] = 'div class="form-group"';
        $this->wrappers['pair']['emptyContainer'] = '';
        $this->wrappers['pair']['.error'] = 'has-error';
        $this->wrappers['label']['container'] = '';
        $this->wrappers['label']['requiredsuffix'] = '<span class="required"> *</span>';
        $this->wrappers['control']['container'] = 'div class="col-md-6 col-sm-6 col-xs-12"';
        $this->wrappers['control']['radio-container'] = 'div class="col-md-6 col-sm-6 col-xs-12 radio-container"';
        $this->wrappers['control']['checkbox-container'] = 'div class="col-md-6 col-sm-6 col-xs-12 checkbox-container"';
        $this->wrappers['control']['containerDual'] = 'div class="col-md-3 col-sm-3"';
        $this->wrappers['control']['description'] = 'span class=help-block';
        $this->wrappers['control']['errorcontainer'] = 'span class=help-block';
        $this->wrappers['control']['.button'] = '';
        $this->wrappers['control']['dash'] = 'div class=guion';
        $this->wrappers['control']['icon'] = 'span class="fa form-control-feedback" aria-hidden="true"';
        $this->wrappers['control']['.submit'] = '';
        $this->wrappers['item']['glyph'] = 'i class="glyphicon glyphicon-exclamation-sign"';
        $this->wrappers['error']['container'] = 'div';
        $this->wrappers['error']['item'] = 'div class="alert alert-danger" role="alert"';
        $this->wrappers['buttons']['container'] = 'div class="col-md-offset-3"';
        $this->wrappers['buttons']['groupcontainer'] = 'div class="col-md-12 col-sm-12 col-xs-12"';
        $this->wrappers['empty']['empty'] = '';
        $this->wrappers['upload']['container'] = 'div class="clearfix" dropzone="yes"';
        $this->wrappers['upload']['subcontainer'] = 'div class="dropzone"';
        $this->wrappers['upload']['filecontainer'] = 'div class="fallback"';
        $this->wrappers['template']['container'] = 'div class="col-md-12 col-sm-12 col-xs-12"';
        $this->wrappers['tab-head']['container'] = 'div class="" role="tabpanel" data-example-id="togglable-tabs"';
        $this->wrappers['tab-head']['ul'] = 'ul class="nav nav-tabs bar_tabs" role="tablist"';
        $this->wrappers['tab-head']['li'] = 'li role="presentation"';
        $this->wrappers['tab-head']['a'] = 'a data-toggle="tab" role="tab';
        $this->wrappers['tab-head']['icon'] = 'i';
        $this->wrappers['tab-body']['hidden'] = 'input name="selectedTab" type="hidden"';
        $this->wrappers['tab-body']['container'] = 'div class="tab-content" role="tabpanel"';
        $this->wrappers['tab-body']['tab'] = 'div class="tab-pane"';
        $this->wrappers['tab-body']['subcontainer'] = 'div class="col-md-12 col-sm-12 col-xs-12"';

        if ($assignedToForm) {
            $this->form = $assignedToForm;
        }
    }

    public function renderBegin(): string
    {
        $class = $this->form->getElementPrototype()->getAttribute('class');
        $this->form->getElementPrototype()->setAttribute(
            'class',
            $class . ' form-horizontal form-label-left presenter-action'
        );
        return parent::renderBegin();
    }

    public function renderLabel(Control $control, string $labelReplace = null): Html
    {
        $suffix = $this->getValue('label suffix');
        $suffix .= ($control->isRequired() ? $this->getValue('label requiredsuffix') : '');
        $label = $control->getLabel(!empty($labelReplace) || $labelReplace == '' ? $labelReplace : null);

        if ($control instanceof Checkbox) {
            // force separate rendering of label and checkbox input
            $label = $control->getLabelPart();
            $control->caption = '';
        }

        if ($label instanceof Html) {
            $label->addHtml($suffix);
            if ($control->isRequired()) {
                $label->class($this->getValue('control .required'), true);
            }
            $label->class("control-label col-md-3 col-sm-3 col-xs-12");
        } elseif ($label != null) { // @intentionally ==
            $label->class = "control-label col-md-3 col-sm-3 col-xs-12";
            $label .= $suffix;
        } else {
            $label .= "<div></div>";
        }

        return $this->getWrapper('label container')->setHtml($label);
    }

    public function renderIcon(BaseControl $control): Html
    {
        $el = $control->getControl();
        $icon = $this->getWrapper('control icon');

        if ($el->getAttribute('icon')) {
            $icon->addClass($el->getAttribute('icon'));

            if (strpos($el->getAttribute('icon'), 'left') === false) {
                if (strpos($el->getAttribute('icon'), 'right') !== false) {
                    if ($control instanceof SelectBox) {
                        $icon->addClass('rightSelectBox');
                    }
                } else {
                    $icon->addClass('left');
                }
            }
            $icon->render();
        }
        return $icon;
    }

    public function renderControl(Control $control): Html
    {
        if ($control instanceof RadioList || $control instanceof CheckboxList) {
            $body = $this->getWrapper('control radio-container');
        } elseif ($control instanceof Checkbox) {
            $body = $this->getWrapper('control checkbox-container');
        } else {
            $body = $this->getWrapper('control container');
        }

        if ($this->counter % 2) {
            $body->addClass($this->getValue('control .odd'), true);
        }

        $description = $control->getOption('description');

        if ($description instanceof HtmlStringable) {
            $description = ' ' . $description;
        } elseif ($description != null) { // intentionally ==
            $description = ' ' . $this->getWrapper('control description')->setText($description);
        } else {
            $description = '';
        }

        if ($control->isRequired()) {
            $description = $this->getValue('control requiredsuffix') . $description;
        }

        $control->setOption('rendered', true);
        $el = $control->getControl();

        if ($el->getAttribute('icon')) {
            $body->addClass('has-feedback');
            if (strpos($el->getAttribute('icon'), 'right') === false) {
                $el->addClass('has-feedback-left');
            }
        }

        if ($el instanceof Html && $el->getName() === 'input') {
            $el->addClass($this->getValue("control .$el->type"), true);
        }
        return $body->setHtml($el . $description . $this->renderErrors($control) . $this->renderIcon($control));
    }

    public function renderControls($parent, bool $isTab = false): string
    {
        if (!($parent instanceof Container || $parent instanceof ControlGroup)) {
            throw new InvalidArgumentException(
                'Argument must be Nette\Forms\Container or Nette\Forms\ControlGroup instance.'
            );
        }

        $container = $this->getWrapper('controls container');
        $subcontainer = $this->getWrapper('controls subcontainer');
        $buttons = [];
        $combinedElms = $this->form->getCombinedElements();
        $firstCombined = null;

        foreach ($parent->getControls() as $control) {
            if ($control->getOption('rendered') || $control instanceof HiddenField) {
                continue;
            }

            if ($control instanceof Button) {
                if (!$control->getControlPrototype()->class) {
                    $control->getControlPrototype()->addClass("btn btn-secondary");
                }
                $buttons[] = $control;
            } elseif ($control instanceof Checkbox) {
                if ($buttons) {
                    $subcontainer->addHtml($this->renderPairMulti($buttons));
                    $buttons = [];
                }
                $control->getControlPrototype()->addClass("checkbox");
                $subcontainer->addHtml($this->renderPair($control));
            } elseif (method_exists($control, "render")) {
                $control->getControlPrototype()->addClass('form-control col-md-7 col-xs-12');
                $subcontainer->addHtml($control->render());
            } else {
                if ($buttons) {
                    $subcontainer->addHtml($this->renderPairMulti($buttons));
                    $buttons = [];
                }
                $control->getControlPrototype()->addClass('form-control col-md-7 col-xs-12');
                $combElStart = preg_grep('/^' . $control->name . '/', array_keys($combinedElms));
                $combElEnd = preg_grep('/_' . $control->name . '/', array_keys($combinedElms));

                if (empty($combElStart) && empty($combElEnd)) {
                    $subcontainer->addHtml($this->renderPair($control));
                } elseif (!empty($combElEnd)) {
                    //draw two control side by side
                    $subcontainer->addHtml($this->renderPairDouble(
                        $firstCombined,
                        $control,
                        $combinedElms[$firstCombined->name . '_' . $control->name]['label']
                    ));
                    $firstCombined = null;
                } else {
                    //remember first control from two to draw
                    $firstCombined = $combinedElms[$combElStart[array_key_first($combElStart)]][$control->name];
                }
            }

            if ($control instanceof MultiSelectBox) {
                $control->getControlPrototype()->addClass("select2 select2-hidden-accessible");
            }
        }

        $container->addHtml($subcontainer);
        $s = "";

        if (count($container)) {
            $s .= "\n" . $container . "\n";
        }

        //render buttons to footer (when not in tab)
        if (!$isTab) {
            $s .= $this->renderFooterButtons($buttons);
        }

        return $s;
    }

    public function renderPairDouble(Control $control1, Control $control2, string $replaceLabelString = null): string
    {
        $pair = $this->getWrapper('pair container');

        //first control
        $pair->addHtml($this->renderLabel($control1, $replaceLabelString));

        //set new wrapper sizes
        $lastContainer = $this->wrappers['control']['container'];
        $this->wrappers['control']['container'] = $this->wrappers['control']['containerDual'];
        $pair->addHtml($this->renderControl($control1));

        //dash between
        $dash = $this->getWrapper('control dash');
        $dash->addHtml('-');
        $pair->addHtml($dash->render());
        $pair->addHtml($this->renderControl($control2));

        //return control wrapper
        $this->wrappers['control']['container'] = $lastContainer;
        $pair->class($this->getValue($control1->isRequired() ? 'pair .required' : 'pair .optional'), true);
        $pair->class($control1->hasErrors() || $control2->hasErrors() ? $this->getValue('pair .error') : null, true);
        $pair->class($control1->getOption('class'), true);
        $pair->class($control2->getOption('class'), true);
        if (++$this->counter % 2) {
            $pair->class($this->getValue('pair .odd'), true);
        }
        $pair->id = $control1->getOption('id');
        return $pair->render(0);
    }

    public function renderFooterButtons(array $buttons): string
    {
        $s = '';

        if ($buttons) {
            if (empty($this->form->getGroups())) {
                $container = $this->getWrapper('buttons container');
            } else {
                $container = $this->getWrapper('buttons groupcontainer');
                $this->wrappers['control']['container'] =  'div class="col text-center"';
            }
            $container->addHtml($this->renderPairMulti($buttons));
            if (count($container)) {
                $s .= "\n" . $container . "\n";
            }
            $s = '<div class="ln_solid"></div>' . $s;
        }

        return $s;
    }

    public function renderErrors(Control $control = null, bool $own = true): string
    {
        $ownErrors = $own ? $this->form->getOwnErrors() : $this->form->getErrors();
        $errors = $control ? $control->getErrors() : $ownErrors;

        if (!$errors) {
            return '';
        }

        $container = $this->getWrapper($control ? 'control errorcontainer' : 'error container');
        $item = $this->getWrapper($control ? 'control erroritem' : 'error item');

        $glyph = null;
        if ($errors) {
            $glyph = $this->getWrapper("item glyph");
        }

        foreach ($errors as $error) {
            $item = clone $item;

            if ($error instanceof Html) {
                $item->addHtml($error);
            } else {
                $item->setText($error);
            }

            if ($glyph) {
                $container->addHtml($glyph);
            }

            $container->addHtml($item);
        }

        return "\n" . $container->render($control ? 1 : 0);
    }

    public function renderBody(): string
    {
        $s = $remains = '';

        //generovani skupin prvku
        $defaultContainer = $this->getWrapper('group container');

        foreach ($this->form->getGroups() as $group) {
            if (!$group->getControls() || !$group->getOption('visual')) {
                continue;
            }

            $container = $group->getOption('container', $defaultContainer);
            $container = $container instanceof Html ? clone $container : Html::el($container);
            $id = $group->getOption('id');

            if ($id) {
                $container->id = $id;
            }

            $class = $group->getOption('class');

            if ($class) {
                $container->addAttributes(['class' => $class]);
            }

            $s .= "\n" . $container->startTag();
            $text = $group->getOption('label');

            if ($text instanceof HtmlStringable) {
                $s .= $this->getWrapper('group label')->addHtml($text);
            } elseif ($text != null) { // intentionally ==
                $s .= "\n" . $this->getWrapper('group label')->setText($text) . "\n";
            }

            $text = $group->getOption('description');

            if ($text instanceof HtmlStringable) {
                $s .= $text;
            } elseif ($text != null) { // intentionally ==
                $s .= $this->getWrapper('group description')->setText($text) . "\n";
            }

            $s .= $this->renderControls($group);

            $remains = $container->endTag() . "\n" . $remains;

            if (!$group->getOption('embedNext')) {
                $s .= $remains;
                $remains = '';
            }
        }

        $tabs = $this->form->getTabs();

        if (!empty($tabs)) {
            // generate tab element
            // generate tab header
            $tabsHeaderContainer = $this->getWrapper('tab-head container');
            $s .= "\n" . $tabsHeaderContainer->startTag();
            $ulContainer = $this->getWrapper('tab-head ul');
            $s .= "\n" . $ulContainer->startTag();

            foreach ($tabs as $tab) {
                $href = '#' . $tab->getOption('tabId');

                if ($tab->getOption('selected')) {
                    $liContainer = $this->getWrapper('tab-head li')->addAttributes(
                        ['class' => 'tab-link active']
                    );
                    $aContainer = $this->getWrapper('tab-head a')->addAttributes(
                        ['href' => $href, 'aria-expanded' => 'true']
                    );
                } else {
                    $liContainer = $this->getWrapper('tab-head li')->addAttributes(
                        ['class' => 'tab-link']
                    );
                    $aContainer = $this->getWrapper('tab-head a')->addAttributes(
                        ['href' => $href, 'aria-expanded' => 'false']
                    );
                }
                $liContainer->setAttribute('id', 'tab-link-' . $tab->getOption('tabId'));
                $s .= "\n" . $liContainer->startTag();

                $label = '';
                if ($tab->getOption('icon')) {
                    $label = $this->getWrapper('tab-head icon')
                        ->setAttribute('class', 'fa ' . $tab->getOption('icon')) . '&nbsp;';
                }

                $text = $tab->getOption('label');

                if ($text instanceof HtmlStringable) {
                    $label .= $text;
                } elseif ($text != null) {
                    $label .= htmlspecialchars($text, ENT_NOQUOTES);
                }

                $s .= "\n" . $aContainer->setHtml($label) . "\n";
                $s .= "\n" . $liContainer->endTag();
            }

            $s .= "\n" . $ulContainer->endTag();
            $s .= "\n" . $tabsHeaderContainer->endTag();

            // generate tab body
            $tabsBodyContainer = $this->getWrapper('tab-body container');
            $s .= "\n" . $tabsBodyContainer->startTag();
            $selectedTab = '';

            foreach ($tabs as $tab) {
                $tabContainer = $this->getWrapper('tab-body tab');
                $tabContainer->id = $tab->getOption('tabId');

                if ($tab->getOption('selected')) {
                    $tabContainer->addAttributes(['class' => 'tab-pane active']);
                    $selectedTab = $tab->getOption('tabId');
                }

                $s .= $tabContainer->startTag();
                $s .= $this->renderControls($tab, true);
                $s .= $tabContainer->endTag();
            }

            $s .= $this->getWrapper('tab-body hidden')->setAttribute('value', $selectedTab);
            $s .= "\n" . $tabsBodyContainer->endTag();
        }

        // generate remainder
        $s .= $remains . $this->renderControls($this->form);

        $container = $this->getWrapper('form container');
        $container->setHtml($s);

        return $container->render(0);
    }
}
