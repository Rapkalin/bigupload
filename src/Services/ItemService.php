<?php

namespace App\Services;

use App\Constant\UploadConfig;
use App\Entity\Item;
use App\Repository\UploadCounterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Turns a fully uploaded file into a downloadable item: <showId>.zip + database row.
 */
final class ItemService
{
    public function __construct(
        private readonly string $uploadDir,
        private readonly Filesystem $filesystem,
        private readonly ZipService $zipService,
        private readonly UrlShortenerService $urlShortener,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly UploadCounterRepository $uploadCounterRepository,
    ) {
    }

    public function createFromUpload(string $uploadedFilePath, string $fileName): Item
    {
        $showId = UploadConfig::SHOW_ID_PREFIX . bin2hex(random_bytes(16));
        $itemDir = $this->uploadDir . DIRECTORY_SEPARATOR . $showId;
        $zipName = $showId . '.' . UploadConfig::ZIP_EXTENSION;
        $zipPath = $this->uploadDir . DIRECTORY_SEPARATOR . $zipName;

        try {
            $this->filesystem->mkdir($itemDir);
            $this->filesystem->rename($uploadedFilePath, $itemDir . DIRECTORY_SEPARATOR . $fileName);
            $this->zipService->zipDirectory($this->uploadDir, $zipName, $showId);

            $item = Item::create(
                title: $fileName,
                showId: $showId,
                zipName: $zipName,
                size: filesize($zipPath),
                downloadPageUrl: $this->urlShortener->shorten($this->buildAbsoluteUrl('item.show', $showId)),
                downloadFileUrl: $this->buildAbsoluteUrl('file.download', $showId),
            );
            $this->entityManager->persist($item);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->filesystem->remove([$uploadedFilePath, $zipPath]);
            throw $e;
        } finally {
            $this->filesystem->remove($itemDir);
        }

        $this->uploadCounterRepository->increment();

        return $item;
    }

    private function buildAbsoluteUrl(string $route, string $showId): string
    {
        return getDomaineUrl() . $this->urlGenerator->generate($route, ['showId' => $showId]);
    }
}
