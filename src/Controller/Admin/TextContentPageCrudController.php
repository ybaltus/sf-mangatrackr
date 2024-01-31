<?php

namespace App\Controller\Admin;

use App\Entity\TextContentPage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class TextContentPageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TextContentPage::class;
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
            TextField::new('name_slug')->onlyOnIndex(),
            TextEditorField::new('content'),
            DateTimeField::new('createdAt')->onlyOnIndex(),
            DateTimeField::new('updatedAt')->onlyOnIndex(),
            BooleanField::new('isActivated'),
        ];
    }
}
