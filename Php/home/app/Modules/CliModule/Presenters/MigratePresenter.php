<?php
declare(strict_types=1);

namespace App\Modules\CliModule\Presenters;

use App\Modules\CliModule\Service\Migrator;
use App\Modules\Presenters\SecurePresenter;

/** Presenter pro import dat */
final class MigratePresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Migrace dat'
    ];

    /** @inject */
    public Migrator $migrator;

    public function actionUsers(): void
    {
        // $this->migrator->migrateUsers();
        // $this->flashMessage('Migrace uživatelů proběhla úspěšně');
        $this->redirect('default');
    }

    public function actionWarehousemen(): void
    {
        // $this->migrator->migrateWarehousemen();
        // $this->flashMessage('Migrace skladníků proběhla úspěšně');
        $this->redirect('default');
    }

    public function actionDeliveryItems(): void
    {
        // $this->migrator->migrateDeliveryItems();
        // $this->flashMessage('Migrace skladových položek proběhla úspěšně');
        $this->redirect('default');
    }

    public function actionComplaints(): void
    {
        // $this->migrator->migrateComplaints();
        // $this->flashMessage('Migrace reklamaci zakazniku proběhla úspěšně');
        $this->redirect('default');
    }

    public function actionSalesDataAccess(): void
    {
        // $this->migrator->migrateSalesDataAccess();
        // $this->flashMessage('Migrace přístupu uživatelů k datum analýzy prodeje proběhla úspěšně');
        $this->redirect('default');
    }

    public function actionStavbaHradecFix(): void
    {
        // $this->migrator->migrateStavbaHradec();
        // $this->flashMessage('Migrace stavba == Hraded proběhla úspěšně');
        $this->redirect('default');
    }

    public function actionDeliveryItemsFix(): void
    {
        //$fixed = $this->migrator->deliveryItemsFix(2021);
        //this->flashMessage("Opraveno $fixed stavů dodacích listů");
        $this->redirect('default');
    }
}
