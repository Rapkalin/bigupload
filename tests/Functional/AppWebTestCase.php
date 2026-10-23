<?php

namespace App\Tests\Functional;

use App\Entity\Item;
use App\Repository\UploadCounterRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Empty database tables and upload directory before each test.
 * Test limits (config/services.yaml, when@test): chunks of 8 bytes, files of 20 bytes max.
 */
abstract class AppWebTestCase extends WebTestCase
{
    protected const int CHUNK_SIZE = 8;
    protected const int MAX_FILE_SIZE = 20;
    // REMOTE_ADDR of the KernelBrowser requests
    protected const string CLIENT_IP = '127.0.0.1';

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->createAppClient();

        $connection = $this->getService(Connection::class);
        $connection->executeStatement('DELETE FROM items');
        $connection->executeStatement('DELETE FROM count_downloads');
        $connection->executeStatement('INSERT INTO count_downloads (id, uploaded_files) VALUES (1, 0)');

        $filesystem = new Filesystem();
        $filesystem->remove($this->uploadDir());
        $filesystem->mkdir($this->uploadDir());

        $this->getService('limiter.upload')->create(self::CLIENT_IP)->reset();
    }

    /**
     * @param array{debug?: bool} $options debug: false renders the real error pages instead of the debug ones
     */
    protected function createAppClient(array $options = []): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient($options);
        // Keep the same container for all the requests of a test
        $this->client->disableReboot();
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    protected function getService(string $id): object
    {
        return static::getContainer()->get($id);
    }

    protected function uploadDir(): string
    {
        return static::getContainer()->getParameter('app.upload_dir');
    }

    protected function upload(string $name, string $content, int $chunk = 0, int $chunks = 1, ?string $uploadId = null): array
    {
        $query = ['name' => $name, 'chunk' => $chunk, 'chunks' => $chunks];
        if (null !== $uploadId) {
            $query['upload_id'] = $uploadId;
        }

        $this->client->request('POST', '/handleFile?' . http_build_query($query), content: $content);

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * Sends $content split in chunks like PLupload, returns the last response.
     */
    protected function uploadInChunks(string $name, string $content): array
    {
        $chunks = str_split($content, self::CHUNK_SIZE);
        $uploadId = null;
        foreach ($chunks as $index => $chunk) {
            $response = $this->upload($name, $chunk, $index, count($chunks), $uploadId);
            $uploadId = $response['extraData']['upload_id'] ?? null;
        }

        return $response;
    }

    /**
     * Last created item
     */
    protected function findItem(): ?Item
    {
        return $this->getService(EntityManagerInterface::class)->getRepository(Item::class)->findOneBy([], ['id' => 'DESC']);
    }

    protected function createItem(string $content = 'content', string $expiration = '+7 days'): Item
    {
        $response = $this->upload('photo.jpg', $content);
        self::assertSame(201, $this->client->getResponse()->getStatusCode(), json_encode($response));

        $item = $this->findItem();
        if ('+7 days' !== $expiration) {
            $this->getService(Connection::class)->executeStatement(
                'UPDATE items SET expiration_date = ? WHERE id = ?',
                [(new \DateTimeImmutable($expiration))->format('Y-m-d H:i:s'), $item->getId()]
            );
            $this->getService(EntityManagerInterface::class)->clear();
            $item = $this->findItem();
        }

        return $item;
    }

    protected function zipPath(Item $item): string
    {
        return $this->uploadDir() . '/' . $item->getZipName();
    }

    protected function readZippedFile(Item $item): string
    {
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($this->zipPath($item)));
        $entries = array_map($zip->getNameIndex(...), range(0, $zip->numFiles - 1));
        // The zip only holds the uploaded file, in a directory named after the showId
        self::assertSame([$item->getShowId() . '/', $item->getShowId() . '/' . $item->getTitle()], $entries);
        $content = $zip->getFromName($item->getShowId() . '/' . $item->getTitle());
        $zip->close();

        return $content;
    }

    protected function uploadCount(): int
    {
        return $this->getService(UploadCounterRepository::class)->get();
    }
}
