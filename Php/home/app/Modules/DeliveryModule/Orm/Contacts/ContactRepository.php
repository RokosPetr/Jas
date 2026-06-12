<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Contacts;

use App\Core\Orm\BaseRepository;
use App\Modules\DeliveryModule\Orm\Companies\Depot;

class ContactRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Contact::class];
    }

    public function loadLastOrder(Depot $depot): int
    {
        return $this->findBy(['depot->id' => $depot->id, 'deleted' => false])->countStored();
    }

    public function changeOrder(Contact $contact, int $newOrder): void
    {
        $oldOrder = $contact->order;
        $contactOrders = [$newOrder => $contact];
        $contact->order = 0;
        $this->persist($contact);

        if ($oldOrder > $newOrder) {
            for ($i = $newOrder; $i < $oldOrder; $i++) {
                $tempOption = $this->getBy(['depot->id' => $contact->depot->id, 'order' => $i, 'deleted' => false]);
                if (!$tempOption) {
                    break;
                }
                $tempOption->order = 0;
                $this->persist($tempOption);
                $contactOrders[$i + 1] = $tempOption;
            }
        }

        if ($oldOrder < $newOrder) {
            for ($i = $newOrder; $i > $oldOrder; $i--) {
                $tempOption = $this->getBy(['depot->id' => $contact->depot->id, 'order' => $i, 'deleted' => false]);
                if (!$tempOption) {
                    break;
                }
                $tempOption->order = 0;
                $this->persist($tempOption);
                $contactOrders[$i - 1] = $tempOption;
            }
        }

        foreach ($contactOrders as $order => $tempOption) {
            $tempOption->order = $order;
            $this->persist($tempOption);
        }
    }
}
