<?php

namespace App\Tests\Functional;

class DownloadTest extends AppWebTestCase
{
    public function testDownloadPageShowsTheFile(): void
    {
        $item = $this->createItem();

        $crawler = $this->client->request('GET', '/download/' . $item->getShowId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('#item-title', 'photo.jpg');
        self::assertSelectorTextSame('#item-extension', 'jpg');
        self::assertSelectorTextContains('h2', 'This file expires in 6 days');
        self::assertSame(
            '/downloadFile/' . $item->getShowId(),
            $crawler->filter('#animatedButton')->attr('href')
        );
    }

    public function testFileNameIsEscapedOnTheDownloadPage(): void
    {
        $this->upload('<img src=x onerror=alert(1)>.jpg', 'content');
        $item = $this->findItem();

        $this->client->request('GET', '/download/' . $item->getShowId());

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('<img src=x', $this->client->getResponse()->getContent());
        self::assertSelectorTextSame('#item-title', '<img src=x onerror=alert(1)>.jpg');
    }

    public function testDownloadReturnsTheZip(): void
    {
        $item = $this->createItem();

        $this->client->request('GET', '/downloadFile/' . $item->getShowId());

        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/zip');
        self::assertResponseHeaderSame('Content-Disposition', 'attachment; filename=' . $item->getZipName());
        self::assertResponseHeaderSame('Content-Length', (string) filesize($this->zipPath($item)));
        self::assertResponseHeaderSame('Accept-Ranges', 'bytes');
        self::assertSame(file_get_contents($this->zipPath($item)), $this->streamedContent());
    }

    public function testInterruptedDownloadCanBeResumed(): void
    {
        $item = $this->createItem();

        $this->client->request('GET', '/downloadFile/' . $item->getShowId(), server: ['HTTP_RANGE' => 'bytes=10-']);

        self::assertResponseStatusCodeSame(206);
        self::assertSame(substr(file_get_contents($this->zipPath($item)), 10), $this->streamedContent());
    }

    /**
     * Regression: expired files were still downloadable until the cron deleted them.
     */
    public function testExpiredItemIsNotFound(): void
    {
        $item = $this->createItem(expiration: '-1 minute');

        $this->client->request('GET', '/download/' . $item->getShowId());
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/downloadFile/' . $item->getShowId());
        self::assertResponseStatusCodeSame(404);
    }

    public function testUnknownItemIsNotFound(): void
    {
        $this->client->request('GET', '/download/bgpld-unknown');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/downloadFile/bgpld-unknown');
        self::assertResponseStatusCodeSame(404);
    }

    public function testItemWhoseZipWasDeletedIsNotFound(): void
    {
        $item = $this->createItem();
        unlink($this->zipPath($item));

        $this->client->request('GET', '/downloadFile/' . $item->getShowId());

        self::assertResponseStatusCodeSame(404);
    }

    private function streamedContent(): string
    {
        ob_start();
        $this->client->getResponse()->sendContent();

        return ob_get_clean();
    }
}
