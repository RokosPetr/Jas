<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Presenters;

use App\Modules\BathroomModule\Component\BathroomFilter;
use App\Modules\BathroomModule\Orm\Bathrooms\BathRating;
use App\Modules\Presenters\BasePresenter;

class FilterViewPresenter extends BasePresenter
{
    public array $titles = [
        'default' => 'Virtuální koupelny',
        'preview' => 'Virtuální koupelna'
    ];

    public function getHomeLink(): string
    {
        if (!$this->getUser()->isLoggedIn()) {
            return 'default';
        }
        return parent::getHomeLink();
    }

    public function renderPreview(int $id): void
    {
        $bathroom = $this->orm->bathrooms->getById($id);
        if (!$bathroom) {
            $this->error('Položka nenalezena');
        }
        $this->template->bathroom = $bathroom;
    }

    public function handleRateBathroom(int $bathroomId, int $rating): void
    {
        $bathroom = $this->orm->bathrooms->getById($bathroomId);
        if (!$bathroom || $bathroom->deleted) {
            $this->sendErrorJson(404, 'Položna nenalezena');
        }
        if ($rating < 0 || $rating > 5) {
            $this->sendErrorJson(400, 'Nevalidní hodnota hodnocení');
        }
        $bathRating = new BathRating();
        $bathRating->bathroom = $bathroom;
        $bathRating->rating = $rating;
        $this->orm->bathRatings->persistAndFlush($bathRating);
        $this->sendSuccessJson();
    }

    protected function createComponentBathroomFilter(): BathroomFilter
    {
        return new BathroomFilter($this->orm);
    }
}