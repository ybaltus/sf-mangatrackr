<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Trait\RemoveEditActionTrait;
use App\Entity\UserInvitationCode;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserInvitationCodeCrudController extends AbstractCrudController
{
    use RemoveEditActionTrait;

    public static function getEntityFqcn(): string
    {
        return UserInvitationCode::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            EmailField::new('email'),
            TextField::new('sendingEmailStatus')
                ->hideWhenCreating(),
            TextField::new('codeInvitation')
            ->hideWhenCreating(),
            AssociationField::new('user')
            ->hideWhenCreating(),
            DateTimeField::new('createdAt')
            ->onlyOnIndex(),
            DateTimeField::new('updatedAt')
            ->onlyOnIndex(),
            DateTimeField::new('expiredAt'),
            BooleanField::new('isActivated')
            ->hideWhenCreating()->setDisabled(),
        ];
    }
}
