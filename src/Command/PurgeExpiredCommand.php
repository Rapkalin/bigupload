<?php

namespace App\Command;

use App\Constant\UploadConfig;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Run every hour by the server crontab (see README).
 */
#[AsCommand(name: 'app:purge-expired', description: 'Delete expired uploads, orphan zips and abandoned temporary files')]
final class PurgeExpiredCommand
{
    public function __construct(
        private readonly ItemRepository $itemRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Filesystem $filesystem,
        private readonly string $uploadDir,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        if (!is_dir($this->uploadDir)) {
            $io->success('Nothing to purge, the upload directory does not exist.');

            return Command::SUCCESS;
        }

        $io->success(sprintf(
            'Deleted %d expired item(s), %d orphan zip(s) and %d abandoned temporary file(s).',
            $this->purgeExpiredItems(),
            $this->purgeOrphanZips(),
            $this->purgeAbandonedTemporaryFiles(),
        ));

        return Command::SUCCESS;
    }

    private function purgeExpiredItems(): int
    {
        $expiredItems = $this->itemRepository->findExpired();
        foreach ($expiredItems as $item) {
            $this->filesystem->remove($this->uploadDir . DIRECTORY_SEPARATOR . $item->getZipName());
            $this->entityManager->remove($item);
        }
        $this->entityManager->flush();

        return count($expiredItems);
    }

    /**
     * Zips without database row (e.g. row deleted by hand), kept as long as a normal upload
     */
    private function purgeOrphanZips(): int
    {
        $knownZips = array_flip($this->itemRepository->findAllZipNames());
        $finder = $this->createFinder()
            ->files()
            ->name('*.' . UploadConfig::ZIP_EXTENSION)
            ->date('until ' . UploadConfig::EXPIRATION_DAYS . ' days ago')
            ->filter(static fn (\SplFileInfo $file): bool => !isset($knownZips[$file->getFilename()]));

        return $this->removeAll($finder);
    }

    /**
     * Partial uploads (.part) and item directories left by an interrupted upload or zip
     */
    private function purgeAbandonedTemporaryFiles(): int
    {
        $finder = $this->createFinder()
            ->notName('*.' . UploadConfig::ZIP_EXTENSION)
            ->date('until ' . UploadConfig::TEMP_FILE_MAX_AGE . ' seconds ago');

        return $this->removeAll($finder);
    }

    private function createFinder(): Finder
    {
        return (new Finder())->in($this->uploadDir)->depth(0)->ignoreDotFiles(false);
    }

    private function removeAll(Finder $finder): int
    {
        // Materialized first: the finder iterates over the directory being modified
        $paths = array_keys(iterator_to_array($finder));
        $this->filesystem->remove($paths);

        return count($paths);
    }
}
