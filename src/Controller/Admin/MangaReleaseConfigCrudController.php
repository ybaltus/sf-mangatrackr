<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Trait\RemoveEditActionTrait;
use App\Entity\MangaReleaseConfig;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MangaReleaseConfigCrudController extends AbstractCrudController
{
    use RemoveEditActionTrait;

    public static function getEntityFqcn(): string
    {
        return MangaReleaseConfig::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setDateTimeFormat('short', 'short')
            ->setSearchFields(['manga.title'])
            ->renderContentMaximized()
        ;

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('manga.title')->onlyOnIndex(),
            AssociationField::new('manga')->hideOnIndex(),
            DateTimeField::new('createdAt')->onlyOnIndex(),
        ];
    }
}
