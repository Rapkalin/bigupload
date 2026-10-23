<?php

namespace App\Tests\Functional;

use App\Constant\UploadConfig;
use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Process\Process;

class PurgeExpiredCommandTest extends AppWebTestCase
{
    public function testExpiredItemsAreDeletedAndValidOnesKept(): void
    {
        $expired = $this->createItem(expiration: '-1 minute');
        $expiredZip = $this->zipPath($expired);
        $valid = $this->createItem();

        $this->purge();

        self::assertFileDoesNotExist($expiredZip);
        self::assertFileExists($this->zipPath($valid));
        self::assertSame([$valid->getShowId()], array_map(
            static fn (Item $item): string => $item->getShowId(),
            $this->getService(EntityManagerInterface::class)->getRepository(Item::class)->findAll()
        ));
    }

    /**
     * Regression: the cron compared dates as strings ("November 03" < "October 29"),
     * so a file uploaded at the end of a month was deleted the next day.
     */
    public function testRecentItemUploadedAtTheEndOfAMonthIsKept(): void
    {
        $item = $this->createItem();
        // Uploaded on October 28th, expires on November 4th: still valid on October 29th
        touch($this->zipPath($item), (new \DateTimeImmutable('-1 day'))->getTimestamp());

        $this->purge();

        self::assertFileExists($this->zipPath($item));
        self::assertNotNull($this->findItem());
    }

    public function testOrphanZipsAreDeletedOnlyOnceTheyAreAsOldAsAnExpiredUpload(): void
    {
        $oldOrphan = $this->createFile('bgpld-old.zip', UploadConfig::EXPIRATION_DAYS * 86400 + 60);
        $recentOrphan = $this->createFile('bgpld-recent.zip', 60);

        $this->purge();

        self::assertFileDoesNotExist($oldOrphan);
        self::assertFileExists($recentOrphan);
    }

    public function testAbandonedTemporaryFilesAreDeleted(): void
    {
        $oldPart = $this->createFile('abandoned.part', UploadConfig::TEMP_FILE_MAX_AGE + 60);
        $activePart = $this->createFile('active.part', 60);
        $oldDirectory = $this->uploadDir() . '/bgpld-interrupted';
        mkdir($oldDirectory);
        touch($oldDirectory . '/video.mp4');
        touch($oldDirectory, time() - UploadConfig::TEMP_FILE_MAX_AGE - 60);

        $this->purge();

        self::assertFileDoesNotExist($oldPart);
        self::assertDirectoryDoesNotExist($oldDirectory);
        self::assertFileExists($activePart);
    }

    /**
     * The legacy cron script must keep working with the existing server crontab.
     */
    public function testLegacyCronScriptRunsTheCommand(): void
    {
        $expired = $this->createItem(expiration: '-1 minute');

        $process = new Process([PHP_BINARY, 'scripts/cron/cron.php'], dirname(__DIR__, 2), ['APP_ENV' => 'test']);
        $process->mustRun();

        self::assertStringContainsString('Deleted 1 expired item(s)', $process->getOutput());
        self::assertFileDoesNotExist($this->zipPath($expired));
    }

    private function purge(): void
    {
        $this->assertCommandIsSuccessful(static::runCommand('app:purge-expired'));
    }

    private function createFile(string $name, int $ageInSeconds): string
    {
        $path = $this->uploadDir() . '/' . $name;
        touch($path, time() - $ageInSeconds);

        return $path;
    }
}
