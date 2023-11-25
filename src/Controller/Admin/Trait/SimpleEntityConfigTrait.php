<?php

namespace App\Controller\Admin\Trait;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

trait SimpleEntityConfigTrait
{
    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setDateTimeFormat('short', 'short')
            ->setSearchFields(['name'])
            ->renderContentMaximized()
        ;

        if (true === str_contains($this->getEntityFqcn(), 'MangaStatus')) {
            $crud->setSearchFields(['title']);
        }

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        $nameField = 'name';
        if (true === str_contains($this->getEntityFqcn(), 'MangaStatus')) {
            $nameField = 'title';
        }

        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new($nameField),
            TextField::new("{$nameField}_slug")->onlyOnIndex(),
            DateTimeField::new('createdAt')->onlyOnIndex(),
            DateTimeField::new('updatedAt')->onlyOnIndex(),
            BooleanField::new('isActivated'),
        ];
    }
}
