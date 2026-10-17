<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Trait\RemoveDeleteActionTrait;
use App\Controller\Admin\Trait\SimpleEntityConfigTrait;
use App\Entity\StatusTrack;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

/**
 * @extends AbstractCrudController<StatusTrack>
 */
class StatusTrackCrudController extends AbstractCrudController
{
    use SimpleEntityConfigTrait;
    use RemoveDeleteActionTrait;

    public static function getEntityFqcn(): string
    {
        return StatusTrack::class;
    }
}
