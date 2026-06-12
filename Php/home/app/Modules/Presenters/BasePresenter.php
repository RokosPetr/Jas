<?php
declare(strict_types=1);

namespace App\Modules\Presenters;

use App\Core\Component\Datagrid\DatagridFactory;
use App\Core\Exporter\Exporter;
use App\Service\Mailer;
use App\Service\OrmModel;
use Contributte\MenuControl\UI\IMenuComponentFactory;
use Contributte\MenuControl\UI\MenuComponent;
use Nette\Application\UI\Presenter;

abstract class BasePresenter extends Presenter
{
    public const MSG_INFO = 'info';
    public const MSG_SUCCESS = 'success';
    public const MSG_ERROR = 'error';

    protected OrmModel $orm;
    protected DatagridFactory $datagridFactory;
    protected Mailer $mailer;
    public Exporter $exporter;

    /** @inject */
    public IMenuComponentFactory $menuFactory;

    public string $resource;
    public array $titles = [];

    public function __construct(OrmModel $orm, DatagridFactory $datagridFactory, Mailer $mailer, Exporter $exporter)
    {
        $this->orm = $orm;
        $this->datagridFactory = $datagridFactory;
        $this->mailer = $mailer;
        $this->exporter = $exporter;
        parent::__construct();
    }

    public function getHomeLink(): string
    {
        return ':System:Homepage:';
    }

    protected function startup(): void
    {
        parent::startup();
        $this->resource = ":$this->name:$this->action";
    }

    public function redrawControls(array $controls) : void
    {
        foreach ($controls as $control) {
            $this->redrawControl($control);
        }
    }

    protected function createComponentTopMenu(): MenuComponent
    {
        return $this->menuFactory->create('topMenu');
    }

    protected function sideDialogAjaxHandler(): void
    {
        if ($this->isAjax()) {
            $this->redrawControl('side-dialog');
        }
    }

    protected function sendSuccessJson(): void
    {
        $this->sendJson(['status' => 200, 'message' => 'OK']);
    }

    protected function sendErrorJson(int $code, string $message): void
    {
        $this->sendJson(['status' => $code, 'message' => $message]);
    }
}
