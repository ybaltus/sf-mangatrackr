<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Trait\RemoveDeleteActionTrait;
use App\Controller\Admin\Trait\SimpleEntityConfigTrait;
use App\Entity\MangaStatus;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class MangaStatusCrudController extends AbstractCrudController
{
    use SimpleEntityConfigTrait;
    use RemoveDeleteActionTrait;

    public static function getEntityFqcn(): string
    {
        return MangaStatus::class;
    }
}
