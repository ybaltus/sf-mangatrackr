<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Trait\ReadOnlyTrait;
use App\Entity\Manga;
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
    use ReadOnlyTrait;

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

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextField::new('titleSlug'),
            TextField::new('titleAlternative')->onlyOnDetail(),
            TextareaField::new('description')->onlyOnDetail(),
            ArrayField::new('mangaType')->onlyOnDetail(),
            ArrayField::new('editor')->onlyOnDetail(),
            TextField::new('author'),
            TextField::new('designer')->onlyOnDetail(),
            NumberField::new('nbChapters')->onlyOnDetail(),
            DateTimeField::new('publishedAt'),
            BooleanField::new('isAdult'),
            TextField::new('mangaStatus.title'),
            DateTimeField::new('createdAt'),
            DateTimeField::new('updatedAt'),
            BooleanField::new('isActivated')->setDisabled(),
            AssociationField::new('mangaStatistic')->onlyOnDetail()
                ->setTemplatePath('admin/fields/manga/manga_statistic.html.twig'),
            AssociationField::new('mangaJikanAPI')->onlyOnDetail()
                ->setTemplatePath('admin/fields/manga/manga_jikan_api.html.twig'),
        ];
    }
}
