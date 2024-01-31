<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Trait\RemoveDeleteActionTrait;
use App\Controller\Admin\Trait\SimpleEntityConfigTrait;
use App\Entity\StatusTrack;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class StatusTrackCrudController extends AbstractCrudController
{
    use SimpleEntityConfigTrait;
    use RemoveDeleteActionTrait;

    public static function getEntityFqcn(): string
    {
        return StatusTrack::class;
    }
}
