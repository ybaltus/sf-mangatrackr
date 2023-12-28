<?php

namespace App\Controller\Admin;

use App\Entity\UserResetPassword;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserResetPasswordCrudController extends AbstractCrudController
{
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT)
        ;
    }

    public static function getEntityFqcn(): string
    {
        return UserResetPassword::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('sendingEmailStatus')
                ->hideWhenCreating(),
            TextField::new('resetCode')
                ->hideWhenCreating(),
            AssociationField::new('user')
                ->hideWhenCreating(),
            DateTimeField::new('createdAt')
                ->onlyOnIndex(),
            DateTimeField::new('expiredAt'),
            BooleanField::new('isActivated')
                ->hideWhenCreating()->setDisabled(),
        ];
    }
}
