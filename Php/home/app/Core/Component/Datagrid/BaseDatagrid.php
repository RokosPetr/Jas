<?php
declare(strict_types=1);

namespace App\Core\Component\Datagrid;

use App\Core\Exporter\Exporter;
use App\Core\Orm\BaseEntity;
use App\Core\Orm\BaseRepository;
use App\Core\Utils\ConditionParser\ConditionParser;
use App\Core\Utils\DateTime;
use App\Modules\Presenters\BasePresenter;
use App\Modules\Presenters\SecurePresenter;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Forms\Container;
use Nette\Forms\Controls\Button;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\SessionSection;
use Nette\Utils\Paginator;
use Nette\Utils\Strings;
use Nextras\Datagrid\Datagrid;
use Nextras\Orm\Collection\ICollection;

/**
 * Custom datagrid extension
 * Added:
 *  - integration with Core and ORM
 *  - more ways of filtering
 *  - top actions
 *  - checkboxes
 *  - legend
 *  - handling of conditions (for checkboxes, actions, legend)
 */
class BaseDatagrid extends Datagrid
{
    /** @persistent */
    public $orderType = ICollection::ASC;

    public int $countPerPage = 10;
    public array $searchFilter = [];

    /** @var array */
    protected $filterDefaults = [];

    /** @var BaseColumn[] */
    protected $columns = [];

    /** @var BaseColumn[] */
    protected array $otherColumns = [];

    protected array $exports = [
        Exporter::TYPE_XLS => ['title' => 'XLS'],
        Exporter::TYPE_CSV => ['title' => 'CSV']
    ];

    protected string $sessionId;
    protected array $topActions = [];
    protected array $rowActions = [];
    protected array $filterColumns = [];
    protected array $selectedRows = [];
    protected ?bool $selectAllRows = null;
    protected bool $multiWordSearch = false;
    public bool $showFilter = false;
    public bool $showSetting = false;
    protected array $legend = [];
    public DatagridSetting $settings;
    protected SessionSection $session;
    public BaseRepository $repository;
    protected array $userSetting = [];

    public function __construct(BaseRepository $repository, string $sessionId = '')
    {
        $this->repository = $repository;
        $this->settings = new DatagridSetting($this);
        $this->sessionId = $sessionId;

        $this->setRowPrimaryKey('id');
        $this->setDataSourceCallback([$this, 'getDataSource']);
        $this->setColumnGetterCallback([$this, 'getColumnData']);
        $this->addCellsTemplate(__DIR__ . '/templates/grid.cells.latte');
        $this->monitor(Presenter::class, function (): void {
            $sessionKey = $this->getName();
            if ($this->sessionId) {
                $sessionKey .= '_' . $this->sessionId;
            }
            $sessionKey .= '_datagrid';
            $this->session = $this->getPresenter()->getSession($sessionKey);
        });
        $this->monitor(SecurePresenter::class, function (): void {
            $this->userSetting = $this->getPresenter()->getUserSetting($this->getName());
        });
    }

    /** Load state information (attribute values sent via url params or set in session) */
    public function loadState(array $params): void
    {
        $defaultOrderColumn = $this->orderColumn ?? $this->settings->defaultOrderColumn;
        $defaultOrderType = $this->orderType;
        $sessionStateParams = [
            'filter', 'page', 'countPerPage', 'orderColumn', 'orderType', 'searchFilter', 'showFilter', 'showSetting', 'selectedRows'
        ];

        foreach ($sessionStateParams as $stateParam) {
            if (isset($this->session->$stateParam) && !isset($params[$stateParam])) {
                $this->$stateParam = $this->session->$stateParam;
            }
        }

        $isSortAction = $this->getPresenter()->getParameter('do') === "$this->name-sort";
        $isPaginateAction = $this->getPresenter()->getParameter('do') === "$this->name-paginate";
        $isCountAction = $this->getPresenter()->getParameter('do') === "$this->name-count";

        if ($isSortAction && !isset($params['orderColumn'])) {
            $this->orderColumn = $defaultOrderColumn;
            $this->orderType = $defaultOrderType;
        }

        if ($isPaginateAction && !isset($params['page'])) {
            $this->page = 1;
        }

        if ($isCountAction && !isset($params['count'])) {
            $this->countPerPage = 10;
        }

        if (!$isCountAction) {
            $this->countPerPage = $this->session->countPerPage ?? 10;
        }

        $this->setPagination($this->countPerPage, [$this, 'getPaginatorData']);
        parent::loadState($params);
    }

    public function addTopAction(string $actionName, string $label = '', array $actionParams = []): BaseAction
    {
        $action = new BaseAction($actionName, $actionParams);
        $action->setLabel((empty($label) ? $actionName : $label));
        return $this->topActions[] = $action;
    }

    public function addExport(string $type, string $title, string $handle): void
    {
        $this->exports[$type] = ['title' => $title, 'handle' => $handle];
    }

    public function addColumn($name, $label = null): BaseColumn
    {
        if (!$this->getRowPrimaryKey()) {
            $this->setRowPrimaryKey($name);
        }

        $label = $label ?: ucfirst($name);
        return $this->columns[$name] = new BaseColumn($name, $label, $this);
    }

    public function addOtherColumn($name, $label = null): BaseColumn
    {
        $label = $label ?: ucfirst($name);
        return $this->otherColumns[$name] = new BaseColumn($name, $label, $this);
    }

    protected function transformBooleanValuesToInt(array $values): array
    {
        return array_map(static fn($value) => is_bool($value) ? (int) $value : $value, $values);
    }

    public function addRowAction(
        string $actionName,
        string $label = '',
        string $icon = BaseAction::UNDEFINED_ACTION_ICON,
        array $params = []
    ): BaseAction {

        $action = new BaseAction($actionName, $params);
        $action->setLabel((empty($label) ? $actionName : $label));
        $action->setIconImage($icon);

        if ($actionName == 'delete') {
            $action->setDialog('Potvrzení', 'Přejete si opravdu odstranit položku?');
        }

        return $this->rowActions[$actionName] = $action;
    }

    public function existsRowAction(string $actionName): bool
    {
        return isset($this->rowActions[$actionName]);
    }

    public function addLegend(string $label, string $class, string $condition, bool $addToStart = false): void
    {
        $legend = new GridLegend($label, $class, $condition);

        if ($addToStart) {
            $this->legend = array_merge([$label => $legend], $this->legend);
        } else {
            $this->legend[$label] = $legend;
        }
    }

    public function setMultiWordSearch(): void
    {
        $this->multiWordSearch = true;
    }

    public function getColumns(): array
    {
        if (empty($this->userSetting['columns'])) {
            return $this->columns;
        }

        $availableColumns = $this->getAvailableColumns();
        $columns = [];

        foreach ($this->userSetting['columns'] as $userColumn) {
            if (isset($availableColumns[$userColumn])) {
                $columns[$userColumn] = $availableColumns[$userColumn];
            }
        }

        return $columns ?: $this->columns;
    }

    public function getAvailableColumns(): array
    {
        return array_merge($this->columns, $this->otherColumns);
    }

    protected function getColumnOption(): array
    {
        $option = [];
        /** @var BaseColumn $column */
        foreach ($this->getAvailableColumns() as $columnName => $column) {
            $option[$columnName] = $column->label;
        }
        return $option;
    }

    /**
     * Returns data for columns
     * Applies all filters and pagination
     * Allows returning only specific row by primary key
     *
     * @param mixed $key
     * @return mixed
     */
    protected function getData($key = null)
    {
        if (!$this->data) {
            $onlyRow = $key !== null && $this->getPresenter()->isAjax();

            $filterDataSource = $this->getMergedFilterDataSource();

            if (!$onlyRow && $this->paginator) {
                $itemsCount = call_user_func($this->paginatorItemsCountCallback, $filterDataSource);
                $this->paginator->setItemCount($itemsCount);

                if ($this->paginator->page !== $this->page) {
                    $this->paginator->page = $this->page = 1;
                }
            }

            $this->data = call_user_func(
                $this->dataSourceCallback,
                $filterDataSource,
                $this->getOrder(),
                $onlyRow ? null : $this->paginator
            );
        }

        if ($key === null) {
            return $this->data;
        }

        foreach ($this->data as $row) {
            if ($this->getter($row, $this->getRowPrimaryKey()) == $key) {
                return $row;
            }
        }

        throw new \Exception('Row not found');
    }

    /**
     * Rewrites nextras datagrid render, adding new fields to template etc
     */
    public function render(): void
    {
        $availableColumns = $this->getAvailableColumns();
        // prevent from saving non-existing column into session
        if (isset($this->orderColumn) && !isset($availableColumns[$this->orderColumn])) {
            // if column does not exist, nullify it
            $this->orderColumn = null;
        }

        $tpl = $this->getTemplate();

        if ($this->filterFormFactory) {
            $this['form']['filter']->setDefaults($this->filter);
        }

        $tpl->gridSetting = $this->settings;
        $tpl->form = $this['form'];
        $tpl->data = $this->getData();
        $tpl->columns = $this->getColumns(); // after saved setting processing

        $tpl->columnOption = $this->getColumnOption();

        if ($this->filterFormFactory) {
            $this->setFilterColumns();
        }

        $tpl->filterColumns = $this->filterColumns; //filter columns
        $tpl->selectedRows = $this->selectedRows;
        $tpl->selectedAllRows = $this->selectAllRows;
        $tpl->rowActions = $this->getAllowedActions();
        $tpl->editRowKey = $this->editRowKey;
        $tpl->rowPrimaryKey = $this->getRowPrimaryKey();
        $tpl->paginator = $this->paginator;
        $tpl->topActions = $this->topActions;
        $tpl->exports = $this->exports;

        $tpl->cellsTemplates = $this->cellsTemplates;
        $tpl->legendData = $this->evaluateLegendConditions();
        $tpl->legends = $this->legend;
        $tpl->sendOnlyRowParentSnippet = $this->sendOnlyRowParentSnippet;
        $tpl->setFile($this->settings->templateFile);
        $tpl->fulltextColumns = $this->getTranslatedFulltextColumns($this->settings->fulltextColumns);
        $tpl->showFulltextTooltip = $this->settings->showFulltextTooltip;
        $tpl->query = !empty($this->searchFilter["query"]) ? $this->searchFilter["query"] : "";
        $tpl->isRestorable = $this->repository->isRestorable();

        $tpl->checkboxesData = null;

        if ($this->settings->shownCheckboxes && !is_null($this->settings->checkboxesCondition)) {
            $tpl->checkboxesData = $this->evaluateCheckboxConditions();
        }

        $tpl->showFilterCancel = !empty($this->searchFilter)
            || !self::areFilterValuesSame($this->filterDataSource, $this->filterDefaults);

        $tpl->setFilter = $this->getMergedFilterDataSource();
        $tpl->translates = $this->settings->defaultTranslates;

        $this->onRender($this);
        $tpl->render();
    }

    /** Translate columns for fulltext search field title */
    public function getTranslatedFulltextColumns(array $columns): array
    {
        $fulltextColumns = [];
        $availableColumns = $this->getAvailableColumns();

        foreach ($columns as $column) {
            if (isset($availableColumns[$column])) {
                $fulltextColumns[] = $availableColumns[$column]->label;
            }
        }

        return $fulltextColumns;
    }

    /**
     * Get allowed actions for each row based on
     *  - evaluating action conditions
     *  - user privileges
     *  - entity state (active, cancelled)
     */
    private function getAllowedActions(): array
    {
        $presenter = $this->getPresenter();
        $allowedActions = [];

        foreach ($this->getData() as $record) {
            if (!isset($record->id)) {
                continue;
            }

            $isRecordCancelled = $record instanceof BaseEntity
                && $record->isAttached()
                && $record->getRepository()->isRestorable()
                && $record->deleted;

            // for deleted entities only restore and preview actions are allowed
            $rowDefaultActions = $isRecordCancelled
                ? array_filter(
                    $this->rowActions,
                    static fn($actionName) : bool => Strings::contains($actionName, 'preview')
                        || Strings::contains($actionName, 'restore'),
                    ARRAY_FILTER_USE_KEY
                )
                : $this->rowActions;

            // user privileges and action conditions filter
            $rowAllowedActions = array_filter(
                $rowDefaultActions,
                static function (BaseAction $action) use ($presenter, $record) : bool {
                    $actionLink = $action->currentPresenter ? ":$presenter->name:$action->linkAction" : $action->link;

                    return $presenter->getUser()->isAllowed($actionLink)
                        && (
                            empty($action->expresion)
                            || (!empty($action->expresion) && $action->expresion->evaluate($record))
                        );
                }
            );

            $allowedActions[$record->id] = $rowAllowedActions;
        }

        return $allowedActions;
    }

    /**
     * Sort datagrid actions based on desired fixed first and last actions
     * @param BaseAction[] $actions [$actionName => $baseAction]
     * @param string[] $firstActionNames first action names order
     * @param string[] $lastActionNames last action names order
     * @return array
     */
    public static function sortActions(array $actions, array $firstActionNames, array $lastActionNames): array
    {
        $firstActions = [];
        $lastActions = [];

        foreach ($firstActionNames as $actionName) {
            if (isset($actions[$actionName])) {
                $firstActions[$actionName] = $actions[$actionName];
                unset($actions[$actionName]);
            }
        }

        foreach ($lastActionNames as $actionName) {
            if (isset($actions[$actionName])) {
                $lastActions[$actionName] = $actions[$actionName];
                unset($actions[$actionName]);
            }
        }

        return $firstActions + $actions + $lastActions;
    }

    /**
     * Get legend data info for rows, evaluate condition
     * -- set $key as key for row
     */
    private function evaluateLegendConditions() : array
    {
        $legends = [];

        foreach ($this->getData() as $key => $value) {
            // for possible legends evaluate conditions and assign first matching
            foreach ($this->legend as $legend) {
                if ($legend->expresion->evaluate($value)) {
                    $legends[$key] = $legend;
                    break;
                }
            }
        }

        return $legends;
    }

    /**
     * Get checkboxes data info for rows, evaluate condition
     * -- set $key as key for row
     */
    private function evaluateCheckboxConditions(): array
    {
        $checkedCheckboxes = [];
        $expression = ConditionParser::parse($this->settings->checkboxesCondition);

        foreach ($this->getData() as $key => $value) {
            if ($expression->evaluate($value)) {
                $checkedCheckboxes[$key] = true;
            }
        }

        return $checkedCheckboxes;
    }

    /**
     * Set filtr columns
     */
    private function setFilterColumns(): void
    {
        $availableColumns = $this->getAvailableColumns();

        foreach ($this['form']['filter']->getComponents() as $key => $component) {
            //skip submitButton (cancel, filtr)
            if ($component instanceof SubmitButton || $component->name === 'filter') {
                continue;
            }
            //try find label in columns array
            $columnName = isset($availableColumns[$key]) ? $availableColumns[$key]->label : null;
            //then try find like caption of component
            $componentCaption = method_exists($component, 'getCaption') ? $component->getCaption() : null;
            $caption = $componentCaption ?? $columnName;

            $this->filterColumns[$key] = (object) ['name' => $key, 'label' => $caption];
        }
    }

    /**
     * @return mixed
     */
    public function getColumnData(BaseEntity $row, string $column)
    {
        $types = $row->getMetadata()->getProperty($column)->types;

        if (array_key_exists('datetime', $types) && !is_null($row->{$column})) {
            if ($row->{$column}->format(DateTime::DB_TIME) == '00:00:00') {
                return $row->{$column}->format(DateTime::CZ_SHORT_DATE);
            }

            return $row->{$column}->format(DateTime::CZ_SHORT_DATETIME_WITH_SEC);
        }

        return $row->{$column};
    }

    public function createComponentForm(): Form
    {
        $form = new Form();

        if ($this->filterFormFactory) {
            $form['filter'] = call_user_func($this->filterFormFactory);

            if (!isset($form['filter']['filter'])) {
                $form['filter']->addSubmit('filter', 'Filtrovat');
            }

            if (!isset($form['filter']['cancel'])) {
                $form['filter']->addSubmit('cancel', 'Zrušit');
            }

            $this->prepareFilterDefaults($form['filter']);

            if (!$this->filterDataSource) {
                $this->filterDataSource = $this->filterDefaults;
            }

            $this->prepareOtherFilterComponents($form['filter']);
        }

        if ($this->editFormFactory && ($this->editRowKey !== null || !empty($_POST['edit']))) {
            $data = $this->editRowKey !== null && empty($_POST) ? $this->getData($this->editRowKey) : null;
            $form['edit'] = call_user_func($this->editFormFactory, $data);

            if (!isset($form['edit']['save'])) {
                $form['edit']->addSubmit('save', 'Save');
            }

            if (!isset($form['edit']['cancel'])) {
                $form['edit']->addSubmit('cancel', 'Cancel');
            }

            if (!isset($form['edit'][$this->getRowPrimaryKey()])) {
                $form['edit']->addHidden($this->getRowPrimaryKey());
            }

            $form['edit'][$this->getRowPrimaryKey()]
                ->setDefaultValue($this->editRowKey)
                ->setOption('rendered', true);
        }

        if ($this->globalActions) {
            $actions = array_map(static fn(array $row) => $row[0], $this->globalActions);
            $form['actions'] = new Container();
            $form['actions']->addSelect('action', 'Action', $actions)
                ->setPrompt('- select action -');
            $form['actions']->addCheckboxList('items', '', []);
            $form['actions']->addSubmit('process', 'Do');
        }

        if ($this->translator) {
            $form->setTranslator($this->translator);
        }

        $form->onSubmit[] = [$this, 'processForm'];

        return $form;
    }

    /** Process form filters editation */
    public function processForm(Form $form): void
    {
        $allowRedirect = true;

        if (isset($form['edit'])) {
            if ($form['edit']['save']->isSubmittedBy()) {
                if ($form['edit']->isValid()) {
                    call_user_func($this->editFormCallback, $form['edit']);
                } else {
                    $this->editRowKey = $form['edit'][$this->rowPrimaryKey]->getValue();
                    $allowRedirect = false;
                }
            }

            if (
                $form['edit']['cancel']->isSubmittedBy()
                || ($form['edit']['save']->isSubmittedBy() && $form['edit']->isValid())
            ) {
                $editRowKey = $form['edit'][$this->rowPrimaryKey]->getValue();
                $this->redrawRow($editRowKey);
                $this->getData($editRowKey);
            }

            if ($this->editRowKey !== null) {
                $this->redrawRow($this->editRowKey);
            }
        }

        if (isset($form['filter'])) {
            if (($form->isSubmitted() && !$form['filter']['cancel']->isSubmittedBy())) {
                if (!$form['filter']->isValid()) {
                    $this->redrawControl('rows');
                    return;
                }

                if ($this->paginator) {
                    $this->page = $this->paginator->page = 1;
                }

                $values = $this->filterFormFilter($form['filter']->getValues(true));
                $this->session->filter = $this->filter = $this->filterDataSource = $values;
                unset($this->session->page);
                $this->redrawControl('rows');
            } elseif ($form['filter']['cancel']->isSubmittedBy()) {
                if ($this->paginator) {
                    $this->page = $this->paginator->page = 1;
                }

                $this->filter = $this->filterDataSource = $this->filterDefaults;
                unset($this->session->filter, $this->session->page, $this->session->searchFilter);

                $form['filter']->setValues($this->filter, true);
                $this->searchFilter = [];
                $this->redrawControl('rows');
            }
        }

        if (isset($form['actions']) && $form['actions']['process']->isSubmittedBy()) {
            $action = $form['actions']['action']->getValue();

            if ($action) {
                $rows = [];

                foreach ($this->getData() as $row) {
                    $rows[] = $this->getter($row, $this->rowPrimaryKey);
                }

                $ids = array_intersect($rows, $form->getHttpData($form::DATA_TEXT, 'actions[items][]'));
                $callback = $this->globalActions[$action][1];
                $callback($ids, $this);
                $this->data = null;
                $form['actions']->setValues(['action' => null, 'items' => []]);
            }
        }

        if (!$this->presenter->isAjax() && $allowRedirect) {
            $this->redirect('this');
        }
    }

    /**
     * Copy of parent method
     *  - added support for default container values (interval filters)
     */
    private function prepareFilterDefaults(Container $container): void
    {
        $this->filterDefaults = [];

        foreach ($container->getComponents() as $component) {
            if ($component instanceof Button) {
                continue;
            }

            if ($component instanceof Container) {
                $value = [];
                foreach ($component->getComponents() as $containerComponent) {
                    $value[$containerComponent->name] = $containerComponent->getValue();
                }
            } else {
                $value = $component->getValue();
            }

            if (!self::isEmptyValue($value)) {
                $this->filterDefaults[$component->name] = $value;
            }
        }
    }

    /** Copy of parent method */
    private function filterFormFilter(array $values): array
    {
        $filtered = [];

        foreach ($values as $key => $value) {
            $isDefaultDifferent = isset($this->filterDefaults[$key]) && $this->filterDefaults[$key] !== $value;

            if ($isDefaultDifferent || !self::isEmptyValue($value)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Copy of parent method
     *  - added support for empty container (interval filter)
     */
    private static function isEmptyValue($value): bool
    {
        if (is_array($value) && $value !== []) {
            $value = array_filter($value, static fn($arrayValue) : bool => !is_null($arrayValue) && $arrayValue !== '');
        }

        return $value === null || $value === '' || $value === [] || $value === false;
    }

    /**
     * Compare filter values using loose comparison
     *  - added support to recognize 0 and '' as different values
     */
    private static function areFilterValuesSame(array $valuesFilterA, array $valuesFilterB): bool
    {
        if (count($valuesFilterA) !== count($valuesFilterB)) {
            return false;
        }

        foreach ($valuesFilterA as $filterKey => $filterValue) {
            if (!array_key_exists($filterKey, $valuesFilterB) || $filterValue != $valuesFilterB[$filterKey]) {
                return false;
            }

            $compareValue = $valuesFilterB[$filterKey];

            if (is_numeric($filterValue) !== is_numeric($compareValue)) {
                return false;
            }

            if (is_array($filterValue)) {
                foreach ($filterValue as $containerFilterKey => $containerFilterValue) {
                    $containerCompareValue = $compareValue[$containerFilterKey];

                    if (is_numeric($containerFilterValue) !== is_numeric($containerCompareValue)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /** Filter support implementation for interval containers (filters) */
    private function prepareOtherFilterComponents(Container $filterForm): void
    {
        foreach ($filterForm->getComponents() as $name => $component) {
            if ($component instanceof Container) {
                foreach ($component->components as $containerComponent) {
                    if (in_array($containerComponent->getName(), $this->settings->suffixDateTime, false)) {
                        $this->settings->setFormFiltersWithSuffix('formDateTime', $name);
                        continue;
                    }

                    if (in_array($containerComponent->getName(), $this->settings->suffixDate, false)) {
                        $this->settings->setFormFiltersWithSuffix('formDate', $name);
                        continue;
                    }

                    if (in_array($containerComponent->getName(), $this->settings->suffixTime, false)) {
                        $this->settings->setFormFiltersWithSuffix('formTime', $name);
                        continue;
                    }

                    if (in_array($containerComponent->getName(), $this->settings->suffixIntervalTexts, false)) {
                        $this->settings->setFormFiltersWithSuffix('formIntervalTexts', $name);
                        continue;
                    }

                    if (in_array($containerComponent->getName(), $this->settings->suffixIntervalIntegers, false)) {
                        $this->settings->setFormFiltersWithSuffix('formIntervalIntegers', $name);
                    }
                }
            }
        }
    }

    /** Count callback handler */
    public function handleCount(int $countPerPage): void
    {
        $this->session->countPerPage = $countPerPage;
        $this->setPagination($countPerPage, [$this, 'getPaginatorData']);
        $this->redrawControl();
    }

    /** Handle for cancel filter */
    public function handleFilterCancel(): void
    {
        $formFilter = $this['form']['filter'];

        if ($this->paginator) {
            $this->page = $this->paginator->page = 1;
        }

        $this->filter = $this->filterDataSource = $this->filterDefaults;
        unset($this->session->filter, $this->session->page, $this->session->searchFilter);
        $formFilter->setValues($this->filter, true);
        $this->redrawControl('rows');
    }

    /** Handle for toggling grid filter */
    public function handleShowFilter(bool $show): void
    {
        $this->session->showFilter = $this->showFilter = $show;
        $this->redrawControl();
    }

    /** Handle for toggling grid setting */
    public function handleShowSetting(): void
    {
        $this->session->showSetting = $this->showSetting = !$this->showSetting;
        $this->redrawControl();
    }

    /** Handle for save user columns */
    public function handleSaveUserColumns(): void
    {
        $userColumns = $this->presenter->getRequest()->getPost('userColumns');
        if (is_array($userColumns)) {
            $this->userSetting['columns'] = $userColumns;
            $this->presenter->setUserSetting($this->getName(), $this->userSetting);
        }
        $this->handleShowSetting();
    }

    /** Handle for setting default columns */
    public function handleSetDefaultColumns(): void
    {
        unset($this->userSetting['columns']);
        $this->presenter->setUserSetting($this->getName(), $this->userSetting);
        $this->handleShowSetting();
    }

    /** Handle for search */
    public function handleSearchCancel(): void
    {
        $this->searchFilter = [];
        unset($this->session->searchFilter);
        $this->redrawControl();
    }

    /** Handles ajax request for fulltext searching */
    public function handleSearch(): void
    {
        $search = ['query' => $this->getPresenter()->getParameter('query')];
        $this->session->searchFilter = $this->searchFilter = $search;
        $this->redrawControl();
    }

    /**
     * @see parent::handlePaginate
     */
    public function handlePaginate(): void
    {
        $this->session->page = $this->page;
        parent::handlePaginate();
    }

    /**
     * @see parent::handleSort
     */
    public function handleSort(): void
    {
        $this->session->orderColumn = $this->orderColumn;
        $this->session->orderType = $this->orderType;
        parent::handleSort();
    }

    /** Handle for row selection */
    public function handleSelectRow(int $rowId, bool $select): void
    {
        if ($select) {
            $this->selectedRows[$rowId] = $rowId;
        } else {
            unset($this->selectedRows[$rowId]);
        }
        $this->session->selectedRows = $this->selectedRows;

        if ($this->settings->onCheckCallback) {
            call_user_func($this->settings->onCheckCallback, $rowId, $select);
        }

        $this->redrawControl('rows');
    }

    /** Handle for selection all visible rows */
    public function handleSelectAllRows(bool $select): void
    {
        $expression = !is_null($this->settings->checkboxesCondition)
            ? ConditionParser::parse($this->settings->checkboxesCondition)
            : null;
        $this->selectAllRows = $select;

        if (!$this->filterDataSource) {
            $this['form'];
        }

        foreach ($this->getData() as $row) {
            $selectRow = $select && (!$expression || $expression->evaluate($row));

            if ($selectRow) {
                $this->selectedRows[$row->id] = $row->id;
            } else {
                unset($this->selectedRows[$row->id]);
            }

            if ($this->settings->onCheckCallback) {
                call_user_func($this->settings->onCheckCallback, $row->id, $selectRow);
            }
        }

        $this->session->selectedRows = $this->selectedRows;
        $this->redrawControl('rows');
    }

    /**
     * Export currently displayed datagrid content
     * @param string     $type       export type csv|xls
     */
    public function handleExportDatagrid(string $type): void
    {
        set_time_limit(300);

        if (!$this->filterDataSource) {
            $this->createComponentForm();
        }

        /** @var BasePresenter $presenter */
        $presenter = $this->getPresenter();
        $filter = $this->getMergedFilterDataSource();
        $exportColumns = [];

        foreach ($this->getColumns() as $column) {
            if ($column->export) {
                $exportColumns[] = $column;
            }
        }

        $gridItems = $this->repository->getDataForDatagrid($filter, $this->getOrder())->fetchAll();

        $response = $presenter->exporter->exportFromDatagrid($gridItems, $exportColumns, $type, $this->settings->exportFilename);
        $presenter->sendResponse($response);
    }

    /**
     * Get merged filter data source (preFilter, filterDataSource, searchFilter)
     * @return array
     */
    public function getMergedFilterDataSource(): array
    {
        $preFilter = $this->transformBooleanValuesToInt($this->settings->dataSourceFilter);
        // filters on the right have higher priority than those on the left
        $filterDataSource = array_merge($this->filterDataSource, $preFilter);

        // add fulltext columns to data source filter
        if (!empty($this->searchFilter['query'])) {
            $query = $this->multiWordSearch
                ? explode(' ', $this->searchFilter['query'])
                : $this->searchFilter['query'];
            $filterDataSource['query'] = $query;
            $filterDataSource['fulltextColumns'] = $this->settings->fulltextColumns;
        }

        return $filterDataSource;
    }

    public function getDataSource(array $filter, array $order, Paginator $paginator = null): iterable
    {
        return $this->repository->getDataForDatagrid($filter, $order, $paginator);
    }

    public function getPaginatorData(array $filter): int
    {
        return $this->repository->getCount($filter);
    }

    private function getOrder(): array
    {
        $order = $this->orderColumn ? [$this->orderColumn => strtoupper($this->orderType)] : [];
        return $order + $this->settings->forceOrder;
    }
}
