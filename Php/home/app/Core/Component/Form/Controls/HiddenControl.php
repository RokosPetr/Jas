<?php
declare(strict_types = 1);

namespace App\Core\Component\Form\Controls;

use Nette\Forms\Controls\HiddenField;
use Nette\Forms\Helpers;
use Nette\Utils\Html;

class HiddenControl extends HiddenField
{
    public function getControl(): Html
    {
        $this->setOption('rendered', true);
        $el = clone $this->control;

        return $el->addAttributes([
            'name' => $this->getHtmlName(),
            'disabled' => $this->isDisabled(),
            'value' => $this->value,
            'data-nette-rules' => Helpers::exportRules($this->getRules()) ?: null,
        ]);
    }
}
