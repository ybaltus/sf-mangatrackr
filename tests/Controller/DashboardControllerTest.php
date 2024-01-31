<?php

namespace App\Tests\Controller;

use App\Controller\Admin\DashboardController;
use App\Entity\User;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use Symfony\Component\HttpFoundation\Response;

class DashboardControllerTest extends AbstractCrudTestCase implements ControllerTestInterface
{
    private function getTestUser(): User
    {
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        return $userRepository->findOneByEmail(self::TEST_ADMIN_EMAIL);
    }

    protected function getControllerFqcn(): string
    {
        return DashboardController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testHTTPResponseSuccess(): void
    {
        //        $client = static::createClient();

        // Login User
        $testUser = $this->getTestUser();
        $this->client->loginUser($testUser);

        $crawler = $this->client->request('GET', '/admin_dashboard');

        $this->assertResponseRedirects();
    }

    public function testHTTPResponseFailed(): void
    {
        $crawler = $this->client->request('GET', '/admin_dashboard');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
