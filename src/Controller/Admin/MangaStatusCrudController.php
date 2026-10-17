<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Trait\RemoveDeleteActionTrait;
use App\Controller\Admin\Trait\SimpleEntityConfigTrait;
use App\Entity\MangaStatus;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

/**
 * @extends AbstractCrudController<MangaStatus>
 */
class MangaStatusCrudController extends AbstractCrudController
{
    use SimpleEntityConfigTrait;
    use RemoveDeleteActionTrait;

    public static function getEntityFqcn(): string
    {
        return MangaStatus::class;
    }
}
