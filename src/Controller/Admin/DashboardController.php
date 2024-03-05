<?php

namespace App\Controller\Admin;

use App\Entity\Editor;
use App\Entity\Fantrad;
use App\Entity\Manga;
use App\Entity\MangaReleaseConfig;
use App\Entity\MangaStatus;
use App\Entity\MangaType;
use App\Entity\StatusTrack;
use App\Entity\TextContentPage;
use App\Entity\User;
use App\Entity\UserInvitationCode;
use App\Entity\UserResetPassword;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin_dashboard', name: 'admin_index')]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect(
            $adminUrlGenerator->setController(MangaCrudController::class)->setAction(Action::INDEX)->generateUrl()
        );
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('MangaSync');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Revenir au site', 'fa-solid fa-house-user', 'home_index');

        yield MenuItem::section('Mangas');
        yield MenuItem::LinkToCrud('Editor', 'fa fa-user-pen', Editor::class);
        yield MenuItem::LinkToCrud('Fantrad', 'fa-brands fa-jedi-order', Fantrad::class);
        yield MenuItem::LinkToCrud('Manga', 'fa fa-book', Manga::class);
        yield MenuItem::LinkToCrud('MangaStatus', 'fa fa-info-circle', MangaStatus::class);
        yield MenuItem::LinkToCrud('MangaType', 'fa fa-info-circle', MangaType::class);
        yield MenuItem::LinkToCrud('StatusTrack', 'fa fa-info-circle', StatusTrack::class);

        yield MenuItem::section('Users');
        yield MenuItem::LinkToCrud('User', 'fa fa-user', User::class);
        yield MenuItem::LinkToCrud('Invitation Code', 'fa fa-envelope', UserInvitationCode::class);
        yield MenuItem::LinkToCrud('Reset password Code', 'fa fa-envelope', UserResetPassword::class);

        yield MenuItem::section('Configurations du site');
        yield MenuItem::LinkToCrud('TextContentPage', 'fa fa-file-lines', TextContentPage::class);
        yield MenuItem::linkToCrud('Mangas Calendar Config', 'fa-solid fa-calendar-plus', MangaReleaseConfig::class);
    }
}
