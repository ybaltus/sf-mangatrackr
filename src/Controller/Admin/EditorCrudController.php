<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Trait\RemoveDeleteActionTrait;
use App\Controller\Admin\Trait\SimpleEntityConfigTrait;
use App\Entity\Editor;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class EditorCrudController extends AbstractCrudController
{
    use SimpleEntityConfigTrait;
    use RemoveDeleteActionTrait;

    public static function getEntityFqcn(): string
    {
        return Editor::class;
    }
}
