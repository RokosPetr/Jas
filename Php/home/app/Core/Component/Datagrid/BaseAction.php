<?php
declare(strict_types=1);

namespace App\Core\Component\Datagrid;

use App\Core\Utils\ConditionParser\ConditionParser;
use App\Core\Utils\ConditionParser\OperatorInterface;
use Nette\SmartObject;
use Nette\Utils\Strings;

/**
 * Datagrid action object
 */
class BaseAction
{
    use SmartObject;

    public const UNDEFINED_ACTION_ICON = 'error_outline';

    /** Presenter action to call - on current presenter */
    public string $linkAction;

    /** Action to call, on any presenter (route) */
    public string $link;

    /** Parameters linked with the action */
    public array $actionParams = [];

    /** Button/menu label */
    public string $label;

    /** CSS class of the label/action */
    public string $class = '';

    /** Icon to show */
    public string $icon = '';

    /** Modal data to use if the action is modal */
    public array $modalData = [];

    /** Condition expression to show the action */
    public string $condition = '';

    /** Parsed condition expression */
    public OperatorInterface $expresion;

    /** Link target frame */
    public string $target = '_self';

    /** Modal dialod options (title, text) */
    public array $dialog = [];

    /** If true, use $linkAction for current presenter, otherwise use $link */
    public bool $currentPresenter = true;

    /** A name of column whose content will be appended to action (used as number badge) */
    public string $iconSuffix;

    /** Default action icons */
    public array $defaultActionIcons = [
        'preview' => 'search',
        'edit' => 'pencil',
        'delete' => 'trash',
        'restore' => 'undo'
    ];

    /** Open in side dialog enabler / parameters holder */
    public ?array $sideDialogData = null;

    /** Initialize with linkAction (current presenter) and its params, or empty */
    public function __construct(string $linkAction = "", array $linkParams = [])
    {
        $this->linkAction = $linkAction; //this will use current presenter
        $this->actionParams = $linkParams;
    }

    /** Set complete link (route) for other presenter */
    public function setLink(string $module, string $presenter, array $params = []): self
    {
        $this->currentPresenter = false;
        $this->link = ":$module:$presenter:$this->linkAction";
        $this->actionParams = $params;
        return $this;
    }

    /** Set action label */
    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /** Set action CSS class */
    public function setClass(string $class): self
    {
        $this->class = $class;
        return $this;
    }

    /** Get action CSS classes */
    public function getClassList(): array
    {
        $classList = [];
        if ($this->class) {
            $classList[] = $this->class;
        }
        if ($this->sideDialogData) {
            $classList[] = 'side-dialog';
        }
        if ($this->modalData) {
            $classList[] = 'previewGrip';
        }
        return $classList;
    }

    /** Get action params */
    public function getActionParams(object $dataObject = null): array
    {
        if (!$dataObject) {
            return $this->actionParams;
        }

        $actionParams = $this->actionParams;

        foreach ($actionParams as $param => $value) {
            if (Strings::startsWith($value, 'row->')) {
                $attr = Strings::substring($value, 5);
                $actionParams[$param] = $dataObject->$attr;
            }
        }

        return $actionParams;
    }

    /** Set action icon */
    public function setIconImage(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    /** Get action icon */
    public function getIconImage(): string
    {
        if ($this->icon && $this->icon !== self::UNDEFINED_ACTION_ICON) {
            return $this->icon;
        }
        return $this->defaultActionIcons[$this->linkAction] ?? self::UNDEFINED_ACTION_ICON;
    }

    /** Set link target frame */
    public function setTarget(string $target): self
    {
        $this->target = $target;
        return $this;
    }

    /** Set modal dialog parameters */
    public function setModalData(array $modalParams): self
    {
        $this->modalData = $modalParams;
        return $this;
    }

    /** Set modal dialog config */
    public function setDialog(string $title, string $text): self
    {
        $this->dialog = ['title' => $title, 'text' => $text];
        return $this;
    }

    /** Set column to use as icon suffix (badge) */
    public function setIconSuffix(string $columnName): self
    {
        $this->iconSuffix = $columnName;
        return $this;
    }

    /** Set and parse action condition */
    public function setCondition(string $condition): self
    {
        $this->condition = $condition;
        $this->expresion = ConditionParser::parse($condition);
        return $this;
    }

    /** Link will open in modal window sliding from right */
    public function setSideDialog(bool $enableHistory = true): self
    {
        $this->sideDialogData = [
            'history' => $enableHistory
        ];
        return $this;
    }
}
