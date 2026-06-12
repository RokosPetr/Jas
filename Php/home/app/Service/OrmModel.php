<?php
declare(strict_types=1);

namespace App\Service;

use App\Modules\BathroomModule\Orm\Bathrooms\BathItemLinkRepository;
use App\Modules\BathroomModule\Orm\Bathrooms\BathPictureRepository;
use App\Modules\BathroomModule\Orm\Bathrooms\BathRatingRepository;
use App\Modules\BathroomModule\Orm\Bathrooms\BathroomRepository;
use App\Modules\BathroomModule\Orm\Parameters\BathOptionRepository;
use App\Modules\BathroomModule\Orm\Parameters\BathParameterRepository;
use App\Modules\CliModule\Orm\Imports\ImportRepository;
use App\Modules\DeliveryModule\Orm\Addresses\AddressRepository;
use App\Modules\DeliveryModule\Orm\Companies\CompanyRepository;
use App\Modules\DeliveryModule\Orm\Companies\DepotRepository;
use App\Modules\DeliveryModule\Orm\Companies\GroupRepository;
use App\Modules\DeliveryModule\Orm\Contacts\ContactRepository;
use App\Modules\DeliveryModule\Orm\CustomerComplaints\CustomerComplaintRepository;
use App\Modules\DeliveryModule\Orm\DeliveryItems\DeliveryItemRepository;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNoteRepository;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\NoteItemRepository;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\NoteServiceRepository;
use App\Modules\DeliveryModule\Orm\DepotStandChecks\DepotStandCheckRepository;
use App\Modules\DeliveryModule\Orm\DepotStandChecks\MissingDepotStandRepository;
use App\Modules\DeliveryModule\Orm\DepotStandRelocations\DepotStandRelocationRepository;
use App\Modules\DeliveryModule\Orm\SalesData\SalesDataAccessRepository;
use App\Modules\DeliveryModule\Orm\SalesData\SalesDataRepository;
use App\Modules\DeliveryModule\Orm\Services\ServiceGroupRepository;
use App\Modules\DeliveryModule\Orm\Services\ServiceRepository;
use App\Modules\DeliveryModule\Orm\TakingsOverviewCache\TakingsOverviewCacheRepository;
use App\Modules\DeliveryModule\Orm\TakingsOverviewCacheK2\TakingsOverviewCacheK2Repository;
use App\Modules\MtzModule\Orm\MtzItems\MtzGroupRepository;
use App\Modules\MtzModule\Orm\MtzItems\MtzItemRepository;
use App\Modules\MtzModule\Orm\MtzOrders\MtzOrderItemRepository;
use App\Modules\MtzModule\Orm\MtzOrders\MtzOrderRepository;
use App\Modules\StockModule\Orm\CatalogNumbers\CatalogNumberRepository;
use App\Modules\StockModule\Orm\Cubicles\CubicleItemRepository;
use App\Modules\StockModule\Orm\Cubicles\CubicleRepository;
use App\Modules\StockModule\Orm\CustomGroups\CustomGroupRepository;
use App\Modules\StockModule\Orm\Discounts\DiscountGroupRepository;
use App\Modules\StockModule\Orm\Discounts\DiscountStockGroupRepository;
use App\Modules\StockModule\Orm\Discounts\DiscountStockItemRepository;
use App\Modules\StockModule\Orm\MainStorageOrders\MainStorageOrderItemRepository;
use App\Modules\StockModule\Orm\MainStorageOrders\MainStorageOrderRepository;
use App\Modules\StockModule\Orm\ObligatoryItems\ObligatoryItemRepository;
use App\Modules\StockModule\Orm\ObligatoryItems\OrderRepository;
use App\Modules\StockModule\Orm\Producers\ProducerRepository;
use App\Modules\StockModule\Orm\Stands\StandNoteRepository;
use App\Modules\StockModule\Orm\Stands\StandPlateItemRepository;
use App\Modules\StockModule\Orm\Stands\StandPlateRepository;
use App\Modules\StockModule\Orm\Stands\StandRepository;
use App\Modules\StockModule\Orm\StockGroups\StockGroupRepository;
use App\Modules\StockModule\Orm\StockItems\StockItemRepository;
use App\Modules\StockModule\Orm\StockItems\StockVariantRepository;
use App\Modules\StockModule\Orm\StockItems\UnitRepository;
use App\Modules\StockModule\Orm\StockSectors\StockSectorRepository;
use App\Modules\StockModule\Orm\StockSeries\StockSeriesRepository;
use App\Modules\StockModule\Orm\WarehousemanHours\WarehousemanHourRepository;
use App\Modules\StockModule\Orm\WarehousemanItems\WarehousemanItemRepository;
use App\Modules\StockModule\Orm\Warehousemen\WarehousemanRepository;
use App\Modules\SystemModule\Orm\Sessions\SessionRepository;
use App\Modules\SystemModule\Orm\Files\FileRepository;
use App\Modules\SystemModule\Orm\Mails\MailRepository;
use App\Modules\SystemModule\Orm\MenuItems\MenuItemRepository;
use App\Modules\SystemModule\Orm\Phones\PhoneRepository;
use App\Modules\SystemModule\Orm\Resources\ResourceRepository;
use App\Modules\SystemModule\Orm\Roles\RoleRepository;
use App\Modules\SystemModule\Orm\Stores\StoreRepository;
use App\Modules\SystemModule\Orm\StoreSettings\StoreSettingRepository;
use App\Modules\SystemModule\Orm\Users\User;
use App\Modules\SystemModule\Orm\Users\UserRepository;
use App\Modules\SystemModule\Orm\UserSettings\UserSettingRepository;
use App\Modules\TransportModule\Orm\Cars\StoreCarRepository;
use App\Modules\TransportModule\Orm\Drivers\StoreDriverRepository;
use App\Modules\TransportModule\Orm\Transports\StoreTransportItemPartRepository;
use App\Modules\TransportModule\Orm\Transports\StoreTransportItemRepository;
use App\Modules\TransportModule\Orm\Transports\StoreTransportRepository;
use App\Modules\TransportModule\Orm\Transports\StoreTransportTargetRepository;
use App\Modules\WikiModule\Orm\WikiItems\WikiItemRepository;
use App\Modules\WikiModule\Orm\WikiItems\WikiParamRepository;
use Nextras\Orm\Model\Model;


/**
 * @property-read UserRepository $users
 * @property-read UserSettingRepository $userSetting
 * @property-read StoreSettingRepository $storeSettings
 * @property-read RoleRepository $roles
 * @property-read ResourceRepository $resources
 * @property-read MenuItemRepository $menuItems
 * @property-read PhoneRepository $phones
 * @property-read MailRepository $mails
 * @property-read FileRepository $files
 * @property-read StoreRepository $stores
 * @property-read ProducerRepository $producers
 * @property-read StandRepository $stands
 * @property-read StandPlateRepository $standPlates
 * @property-read StandPlateItemRepository $standPlateItems
 * @property-read StandNoteRepository $standNotes
 * @property-read DepotStandCheckRepository $standChecks
 * @property-read DepotStandRelocationRepository $standRelocations
 * @property-read MissingDepotStandRepository $missingStands
 * @property-read ImportRepository $imports
 * @property-read StockItemRepository $stockItems
 * @property-read StockGroupRepository $stockGroups
 * @property-read CustomGroupRepository $customGroups
 * @property-read StockSectorRepository $stockSectors
 * @property-read StockVariantRepository $stockVariants
 * @property-read StockSeriesRepository $stockSeries
 * @property-read ObligatoryItemRepository $obligatoryItems
 * @property-read OrderRepository $obligatoryItemOrders
 * @property-read MainStorageOrderRepository $mainStorageOrders
 * @property-read MainStorageOrderItemRepository $mainStorageOrderItems
 * @property-read UnitRepository $stockUnits
 * @property-read CatalogNumberRepository $catalogNumbers
 * @property-read WarehousemanRepository $warehousemen
 * @property-read WarehousemanHourRepository $warehousemanHours
 * @property-read WarehousemanItemRepository $warehousemanItems
 * @property-read DeliveryItemRepository $deliveryItems
 * @property-read CustomerComplaintRepository $customerComplaints
 * @property-read CompanyRepository $companies
 * @property-read GroupRepository $companyGroups
 * @property-read DepotRepository $companyDepots
 * @property-read AddressRepository $depotAddresses
 * @property-read ContactRepository $contacts
 * @property-read DiscountGroupRepository $discountGroups
 * @property-read DiscountStockItemRepository $discountStockItems
 * @property-read DiscountStockGroupRepository $discountStockGroups
 * @property-read DeliveryNoteRepository $deliveryNotes
 * @property-read NoteItemRepository $deliveryNoteItems
 * @property-read NoteServiceRepository $deliveryNoteServices
 * @property-read ServiceRepository $deliveryServices
 * @property-read ServiceGroupRepository $deliveryServiceGroups
 * @property-read SalesDataRepository $salesData
 * @property-read SalesDataAccessRepository $salesDataAccess
 * @property-read TakingsOverviewCacheRepository $takingsOverviewCache
 * @property-read TakingsOverviewCacheK2Repository $takingsOverviewCacheK2
 * @property-read StoreCarRepository $storeCars
 * @property-read StoreDriverRepository $storeDrivers
 * @property-read StoreTransportRepository $storeTransports
 * @property-read StoreTransportTargetRepository $storeTransportTargets
 * @property-read StoreTransportItemRepository $storeTransportItems
 * @property-read StoreTransportItemPartRepository $storeTransportItemParts
 * @property-read BathParameterRepository $bathParameters
 * @property-read BathOptionRepository $bathOptions
 * @property-read BathroomRepository $bathrooms
 * @property-read BathPictureRepository $bathPictures
 * @property-read BathRatingRepository $bathRatings
 * @property-read BathItemLinkRepository $bathItemLinks
 * @property-read MtzGroupRepository $mtzGroups
 * @property-read MtzItemRepository $mtzItems
 * @property-read MtzOrderRepository $mtzOrders
 * @property-read MtzOrderItemRepository $mtzOrderItems
 * @property-read CubicleRepository $cubicles
 * @property-read CubicleItemRepository $cubicleItems
 * @property-read WikiItemRepository $wikiItems
 * @property-read WikiParamRepository $wikiParams
 * @property-read SessionRepository $sessions
 */
class OrmModel extends Model
{
    protected ?User $user = null;

    public function getSysUser(): ?User
    {
        return $this->user;
    }

    public function setSysUser(User $user): void
    {
        $this->user = $user;
    }
}
