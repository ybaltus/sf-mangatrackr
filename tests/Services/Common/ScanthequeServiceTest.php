<?php

declare(strict_types=1);

namespace App\Tests\Services\Common;

use App\Entity\Manga;
use App\Entity\MangaUserTrack;
use App\Entity\StatusTrack;
use App\Entity\User;
use App\Entity\UserTrackList;
use App\Repository\MangaRepository;
use App\Repository\MangaUserTrackRepository;
use App\Repository\StatusTrackRepository;
use App\Services\Common\ScanthequeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ScanthequeServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private ScanthequeService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new ScanthequeService($this->em, $this->logger);
    }

    /**
     * @param class-string $repositoryClass
     * @param string[]     $magicMethods
     */
    private function createRepository(string $repositoryClass, array $magicMethods): MockObject
    {
        return $this->getMockBuilder($repositoryClass)
            ->disableOriginalConstructor()
            ->addMethods($magicMethods)
            ->getMock();
    }

    /**
     * @param array<class-string, MockObject> $repositories
     */
    private function setRepositories(array $repositories): void
    {
        $this->em->method('getRepository')->willReturnCallback(
            static fn (string $class) => $repositories[$class]
        );
    }

    public function testPersistMangasDatasCreatesANewMangaUserTrack(): void
    {
        $manga = new Manga();
        $statusTrack = new StatusTrack();
        $userTrackList = new UserTrackList();
        $user = $this->createMock(User::class);
        $user->method('getUserTrackList')->willReturn($userTrackList);

        $mangaRepository = $this->createRepository(MangaRepository::class, ['findOneByTitleSlug']);
        $mangaRepository->method('findOneByTitleSlug')->with('berserk')->willReturn($manga);
        $this->setRepositories([Manga::class => $mangaRepository]);

        $persisted = [];
        $this->em->expects($this->once())->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $this->em->expects($this->once())->method('flush');

        // The nbChaptersTrack is a string when it comes from an input of the front
        $result = $this->service->persistMangasDatas($statusTrack, $user, [
            ['titleSlug' => 'berserk', 'nbChaptersTrack' => '7'],
        ]);

        $this->assertTrue($result);
        $this->assertCount(1, $persisted);
        $this->assertInstanceOf(MangaUserTrack::class, $persisted[0]);
        $this->assertSame($manga, $persisted[0]->getManga());
        $this->assertSame($userTrackList, $persisted[0]->getUserTrackList());
        $this->assertSame($statusTrack, $persisted[0]->getStatusTrack());
        $this->assertSame(7.0, $persisted[0]->getNbChapters());
    }

    public function testPersistMangasDatasUpdatesAnExistingMangaUserTrack(): void
    {
        $existing = new MangaUserTrack();
        $statusTrack = new StatusTrack();

        $mangaRepository = $this->createRepository(MangaRepository::class, ['findOneByTitleSlug']);
        $mangaRepository->method('findOneByTitleSlug')->willReturn(new Manga());
        $mutRepository = $this->createRepository(MangaUserTrackRepository::class, ['findOneById']);
        $mutRepository->method('findOneById')->with('12')->willReturn($existing);
        $this->setRepositories([Manga::class => $mangaRepository, MangaUserTrack::class => $mutRepository]);

        $this->em->expects($this->once())->method('persist')->with($existing);

        $result = $this->service->persistMangasDatas($statusTrack, $this->createMock(User::class), [
            ['titleSlug' => 'berserk', 'nbChaptersTrack' => 3, 'mut' => '12'],
        ]);

        $this->assertTrue($result);
        $this->assertSame($statusTrack, $existing->getStatusTrack());
        $this->assertSame(3.0, $existing->getNbChapters());
    }

    public function testPersistMangasDatasFailureIsLogged(): void
    {
        $mangaRepository = $this->createRepository(MangaRepository::class, ['findOneByTitleSlug']);
        $mangaRepository->method('findOneByTitleSlug')->willReturn(new Manga());
        $this->setRepositories([Manga::class => $mangaRepository]);

        $user = $this->createMock(User::class);
        $user->method('getUserTrackList')->willReturn(new UserTrackList());
        $this->em->method('flush')->willThrowException(new \RuntimeException('DB down'));
        $this->logger->expects($this->once())->method('error')
            ->with('Unable to persist the mangas datas of the scantheque.', $this->arrayHasKey('exception'));

        $this->assertFalse($this->service->persistMangasDatas(new StatusTrack(), $user, [
            ['titleSlug' => 'berserk', 'nbChaptersTrack' => 1],
        ]));
    }

    public function testPersistMangasDatasWithANonNumericNbChaptersIsRejected(): void
    {
        $mangaRepository = $this->createRepository(MangaRepository::class, ['findOneByTitleSlug']);
        $mangaRepository->method('findOneByTitleSlug')->willReturn(new Manga());
        $this->setRepositories([Manga::class => $mangaRepository]);

        $user = $this->createMock(User::class);
        $user->method('getUserTrackList')->willReturn(new UserTrackList());
        $this->em->expects($this->never())->method('flush');
        $this->logger->expects($this->once())->method('error');

        $this->assertFalse($this->service->persistMangasDatas(new StatusTrack(), $user, [
            ['titleSlug' => 'berserk', 'nbChaptersTrack' => 'abc'],
        ]));
    }

    public function testUpdateMangasDataChangesTheNbChapters(): void
    {
        $mangaUserTrack = new MangaUserTrack();

        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateMangasData($mangaUserTrack, [
            'isUpdateStatusTrack' => false,
            'nbChaptersTrack' => '12.5',
        ]);

        $this->assertTrue($result);
        $this->assertSame(12.5, $mangaUserTrack->getNbChapters());
    }

    public function testUpdateMangasDataChangesTheStatusTrack(): void
    {
        $mangaUserTrack = new MangaUserTrack();
        $statusTrack = new StatusTrack();

        $statusRepository = $this->createRepository(StatusTrackRepository::class, ['findOneByNameSlug']);
        $statusRepository->method('findOneByNameSlug')->with('en-pause')->willReturn($statusTrack);
        $this->setRepositories([StatusTrack::class => $statusRepository]);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateMangasData($mangaUserTrack, [
            'isUpdateStatusTrack' => true,
            'statusTrack' => 'en-pause',
        ]);

        $this->assertTrue($result);
        $this->assertSame($statusTrack, $mangaUserTrack->getStatusTrack());
    }

    public function testUpdateMangasDataFailureIsLogged(): void
    {
        $this->em->method('flush')->willThrowException(new \RuntimeException('DB down'));
        $this->logger->expects($this->once())->method('error')
            ->with('Unable to update the manga datas of the scantheque.', $this->arrayHasKey('exception'));

        $this->assertFalse($this->service->updateMangasData(new MangaUserTrack(), [
            'isUpdateStatusTrack' => false,
            'nbChaptersTrack' => 1,
        ]));
    }
}
