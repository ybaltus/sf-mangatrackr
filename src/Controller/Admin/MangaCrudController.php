<?php

namespace App\Controller\Admin;

use App\Entity\Manga;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MangaCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Manga::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setDateFormat('short')
            ->setSearchFields(['title'])
            ->renderContentMaximized()
        ;

        return $crud;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideWhenCreating()->hideWhenUpdating(),
            TextField::new('title'),
            TextField::new('titleSlug')->hideWhenCreating()->hideWhenUpdating(),
            TextField::new('titleAlternative')->hideOnIndex(),
            TextField::new('urlImg')->hideOnIndex(),
            TextareaField::new('description')->setRequired(true)->hideOnIndex(),
            AssociationField::new('mangaStatus')->setRequired(true)->hideOnIndex()->hideOnDetail(),
            ArrayField::new('mangaType')->hideWhenCreating()->hideWhenUpdating(),
            AssociationField::new('mangaType')->setRequired(true)->hideOnIndex()->hideOnDetail(),
            ArrayField::new('editor')->onlyOnDetail(),
            AssociationField::new('editor')->hideOnIndex()->hideOnDetail(),
            TextField::new('author')->setRequired(true),
            TextField::new('designer')->hideOnIndex(),
            NumberField::new('nbChapters')->setRequired(true),
            DateTimeField::new('publishedAt')->setRequired(true),
            BooleanField::new('isAdult'),
            TextField::new('mangaStatus.title')->onlyOnDetail(),
            DateTimeField::new('createdAt')->hideWhenCreating()->hideWhenUpdating(),
            DateTimeField::new('updatedAt')->hideWhenCreating()->hideWhenUpdating(),
            BooleanField::new('isActivated'),
            AssociationField::new('mangaStatistic')->onlyOnDetail()
                ->setTemplatePath('admin/fields/manga/manga_statistic.html.twig'),
            AssociationField::new('mangaJikanAPI')->onlyOnDetail()
                ->setTemplatePath('admin/fields/manga/manga_jikan_api.html.twig'),
        ];
    }
}
