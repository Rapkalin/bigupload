<?php

namespace App\Tests\Functional;

use App\Constant\UploadConfig;
use App\Tests\Mock\TinyUrlMockResponseFactory;
use PHPUnit\Framework\Attributes\DataProvider;

class UploadTest extends AppWebTestCase
{
    public function testSingleChunkUploadCreatesADownloadableItem(): void
    {
        $response = $this->upload('photo.jpg', 'content');

        self::assertResponseStatusCodeSame(201);
        self::assertSame('success', $response['status']);
        self::assertSame(TinyUrlMockResponseFactory::SHORT_URL, $response['extraData']['download_url']);
        self::assertResponseHeaderSame('Cache-Control', 'no-store, private');

        $item = $this->findItem();
        self::assertSame('photo.jpg', $item->getTitle());
        self::assertSame('jpg', $item->getExtension());
        self::assertSame('content', $this->readZippedFile($item));
        self::assertSame((string) filesize($this->zipPath($item)), $item->getSize());
        self::assertSame(1, $this->uploadCount());
        // Only the zip is left: no .part file, no item directory
        self::assertSame([$item->getZipName()], array_values(array_diff(scandir($this->uploadDir()), ['.', '..'])));
    }

    public function testShowIdIsRandomAndUnguessable(): void
    {
        $item = $this->createItem();

        self::assertMatchesRegularExpression('/^' . UploadConfig::SHOW_ID_PREFIX . '[a-f0-9]{32}$/', $item->getShowId());
        self::assertSame($item->getShowId() . '.zip', $item->getZipName());
    }

    public function testExpirationDateIsSevenDaysAfterUpload(): void
    {
        $item = $this->createItem();

        $expected = $item->getCreatedAt()->modify('+' . UploadConfig::EXPIRATION_DAYS . ' days');
        self::assertSame($expected->format('Y-m-d H:i:s'), $item->getExpirationDate()->format('Y-m-d H:i:s'));
    }

    public function testChunkedUploadRebuildsTheFile(): void
    {
        $content = 'chunk-01chunk-02last';
        $response = $this->uploadInChunks('video.mp4', $content);

        self::assertResponseStatusCodeSame(201);
        self::assertSame(TinyUrlMockResponseFactory::SHORT_URL, $response['extraData']['download_url']);
        self::assertSame($content, $this->readZippedFile($this->findItem()));
    }

    public function testIntermediateChunkReturnsTheUploadIdButNoDownloadUrl(): void
    {
        $response = $this->upload('video.mp4', 'chunk-01', 0, 2);

        self::assertResponseStatusCodeSame(200);
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $response['extraData']['upload_id']);
        self::assertArrayNotHasKey('download_url', $response['extraData']);
        self::assertNull($this->findItem());
    }

    /**
     * Regression: two uploads of a file with the same name used to be written in the same temp file.
     */
    public function testConcurrentUploadsWithTheSameNameDoNotMix(): void
    {
        $alice = $this->upload('photo.jpg', 'ALICE-01', 0, 2)['extraData']['upload_id'];
        $bob = $this->upload('photo.jpg', 'BOB---01', 0, 2)['extraData']['upload_id'];
        self::assertNotSame($alice, $bob);

        $this->upload('photo.jpg', 'ALICE', 1, 2, $alice);
        $aliceItem = $this->findItem();
        $this->upload('photo.jpg', 'BOB', 1, 2, $bob);

        self::assertSame('ALICE-01ALICE', $this->readZippedFile($aliceItem));
        self::assertSame('BOB---01BOB', $this->readZippedFile($this->findItem()));
    }

    /**
     * Regression: anybody could append data to an upload in progress by sending its file name.
     */
    #[DataProvider('invalidUploadIdProvider')]
    public function testFollowingChunksRequireTheUploadId(?string $uploadId): void
    {
        $this->upload('photo.jpg', 'ALICE-01', 0, 2);

        $response = $this->upload('photo.jpg', 'INJECTED', 1, 2, $uploadId);

        self::assertResponseStatusCodeSame(403);
        self::assertSame('error', $response['status']);
        self::assertNull($this->findItem());
    }

    public static function invalidUploadIdProvider(): iterable
    {
        yield 'missing' => [null];
        yield 'unknown' => [str_repeat('a', 32)];
        yield 'path traversal' => ['../../../etc/passwd'];
    }

    /**
     * Regression: a chunk retried by PLupload (e.g. lost response) used to be appended twice.
     */
    public function testRetriedChunkIsNotDuplicated(): void
    {
        $uploadId = $this->upload('photo.jpg', 'chunk-01', 0, 3)['extraData']['upload_id'];
        // First attempt cut by a network error after 5 bytes, then retried by PLupload
        $this->upload('photo.jpg', 'chunk', 1, 3, $uploadId);
        $this->upload('photo.jpg', 'chunk-02', 1, 3, $uploadId);
        $this->upload('photo.jpg', 'chunk-02', 1, 3, $uploadId);
        $this->upload('photo.jpg', 'end', 2, 3, $uploadId);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('chunk-01chunk-02end', $this->readZippedFile($this->findItem()));
    }

    public function testChunkSentBeforeThePreviousOneIsRefused(): void
    {
        $uploadId = $this->upload('photo.jpg', 'chunk-01', 0, 3)['extraData']['upload_id'];

        $this->upload('photo.jpg', 'end', 2, 3, $uploadId);

        self::assertResponseStatusCodeSame(409);
    }

    #[DataProvider('forbiddenFileProvider')]
    public function testForbiddenFileTypesAreRefusedBeforeBeingWritten(string $name): void
    {
        $response = $this->upload($name, '<?php echo "pwned";');

        self::assertResponseStatusCodeSame(415);
        self::assertSame('error', $response['status']);
        self::assertSame([], array_values(array_diff(scandir($this->uploadDir()), ['.', '..'])));
        self::assertSame(0, $this->uploadCount());
    }

    public static function forbiddenFileProvider(): iterable
    {
        yield 'php' => ['shell.php'];
        yield 'double extension' => ['photo.jpg.php'];
        yield 'no extension' => ['README'];
        yield 'html' => ['page.html'];
        yield 'svg' => ['image.svg'];
    }

    public function testUppercaseExtensionsAreAccepted(): void
    {
        $this->upload('PHOTO.JPG', 'content');

        self::assertResponseStatusCodeSame(201);
        self::assertSame('PHOTO.JPG', $this->findItem()->getTitle());
    }

    public function testPathTraversalInTheFileNameIsNeutralised(): void
    {
        $this->upload('../../../evil.jpg', 'content');

        self::assertResponseStatusCodeSame(201);
        $item = $this->findItem();
        self::assertSame('evil.jpg', $item->getTitle());
        self::assertSame('content', $this->readZippedFile($item));
        self::assertFileDoesNotExist(dirname($this->uploadDir(), 3) . '/evil.jpg');
    }

    public function testOversizedChunkIsRefusedAndDeleted(): void
    {
        $this->upload('photo.jpg', str_repeat('x', self::CHUNK_SIZE + 1), 0, 2);

        self::assertResponseStatusCodeSame(413);
        self::assertSame([], glob($this->uploadDir() . '/*.part'));
    }

    public function testFileLargerThanTheMaximumSizeIsRefused(): void
    {
        $tooManyChunks = intdiv(self::MAX_FILE_SIZE, self::CHUNK_SIZE) + 2;
        $this->upload('photo.jpg', 'chunk-01', 0, $tooManyChunks);

        self::assertResponseStatusCodeSame(413);
        self::assertNull($this->findItem());
    }

    public function testInvalidChunkNumberIsRefused(): void
    {
        $this->upload('photo.jpg', 'content', 2, 2);

        self::assertResponseStatusCodeSame(400);
    }

    public function testNewUploadsAreRateLimitedPerIp(): void
    {
        for ($i = 0; $i < UploadConfig::UPLOADS_PER_HOUR; ++$i) {
            $uploadId = $this->upload('photo.jpg', 'chunk-01', 0, 2)['extraData']['upload_id'];
            self::assertResponseStatusCodeSame(200);
        }

        $response = $this->upload('photo.jpg', 'chunk-01', 0, 2);
        self::assertResponseStatusCodeSame(429);
        self::assertSame('error', $response['status']);

        // An upload already started can still be finished
        $this->upload('photo.jpg', 'end', 1, 2, $uploadId);
        self::assertResponseStatusCodeSame(201);
    }

    public function testOnlyPostIsAllowed(): void
    {
        $this->client->request('GET', '/handleFile?name=photo.jpg');

        self::assertResponseStatusCodeSame(405);
    }
}
