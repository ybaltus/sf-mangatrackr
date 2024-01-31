<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Trait\RemoveDeleteActionTrait;
use App\Entity\Fantrad;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class FantradCrudController extends AbstractCrudController
{
    use RemoveDeleteActionTrait;

    public static function getEntityFqcn(): string
    {
        return Fantrad::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setDateTimeFormat('short', 'short')
            ->setSearchFields(['name'])
            ->renderContentMaximized()
        ;

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('name'),
            TextField::new('nameSlug')->onlyOnIndex(),
            UrlField::new('url'),
            ChoiceField::new('language'),
            DateTimeField::new('createdAt')->onlyOnIndex(),
            DateTimeField::new('updatedAt')->onlyOnIndex(),
            BooleanField::new('isActivated'),
        ];
    }
}
