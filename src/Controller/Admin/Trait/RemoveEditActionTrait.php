<?php

declare(strict_types=1);

namespace App\Controller\Admin\Trait;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

trait RemoveEditActionTrait
{
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::EDIT)
        ;
    }
}
