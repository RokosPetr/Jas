<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Stores;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\UpdatableTrait;
use App\Modules\SystemModule\Orm\Users\User;
use App\Modules\TransportModule\Orm\Cars\StoreCar;
use Nextras\Orm\Relationships\ManyHasMany;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                         $id             {primary}
 * @property string                      $name
 * @property string                      $street
 * @property string                      $zipCode
 * @property string|null                 $phone
 * @property string|null                 $email
 * @property User|null                   $manager        {m:1 User, oneSided=true}
 * @property string                      $color
 * @property OneHasMany|User[]           $users          {1:m User::$store}
 * @property ManyHasMany|StoreCar[]      $cars           {m:m StoreCar::$stores}
 */
class Store extends BaseEntity
{
    use UpdatableTrait;

    public const INTERNAL_ICO = 27792803;
    public const OSTRAVA = 4;
    public const OSTRAVA_MAIN_STORAGE = 9;
    public const HLUCIN_MAIN_STORAGE = 10;
    public const LC_MAIN_STORAGE = 11;
    public const MAIN_STORAGE = 90;

    public const MAIN_STORAGES = [
        self::OSTRAVA_MAIN_STORAGE,
        self::HLUCIN_MAIN_STORAGE,
        self::LC_MAIN_STORAGE
    ];
}
