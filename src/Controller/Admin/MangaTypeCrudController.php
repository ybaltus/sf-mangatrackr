<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Trait\RemoveDeleteActionTrait;
use App\Controller\Admin\Trait\SimpleEntityConfigTrait;
use App\Entity\MangaType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class MangaTypeCrudController extends AbstractCrudController
{
    use SimpleEntityConfigTrait;
    use RemoveDeleteActionTrait;

    public static function getEntityFqcn(): string
    {
        return MangaType::class;
    }
}
