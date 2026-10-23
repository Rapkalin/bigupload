<?php

namespace App\Tests\Unit;

use App\Services\UrlShortenerService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class UrlShortenerServiceTest extends TestCase
{
    private const string URL = 'https://bigupload.rapkalin.fr/download/bgpld-abc';

    public function testReturnsTheTinyUrl(): void
    {
        $response = new MockResponse('https://tinyurl.com/abc');
        $service = new UrlShortenerService(new MockHttpClient($response), new NullLogger());

        self::assertSame('https://tinyurl.com/abc', $service->shorten(self::URL));
        self::assertSame('GET', $response->getRequestMethod());
        parse_str(parse_url($response->getRequestUrl(), PHP_URL_QUERY), $query);
        self::assertSame(['url' => self::URL], $query);
        self::assertStringStartsWith('https://tinyurl.com/api-create.php?', $response->getRequestUrl());
    }

    /**
     * Regression: uploads failed (500) when tinyurl.com was unavailable.
     */
    #[DataProvider('failingResponseProvider')]
    public function testFallsBackToTheOriginalUrlWhenTinyUrlFails(MockResponse|\Closure $response): void
    {
        $service = new UrlShortenerService(new MockHttpClient($response), new NullLogger());

        self::assertSame(self::URL, $service->shorten(self::URL));
    }

    public static function failingResponseProvider(): iterable
    {
        yield 'server error' => [new MockResponse('Error', ['http_code' => 500])];
        yield 'not an url' => [new MockResponse('<html>maintenance</html>')];
        yield 'network error' => [static fn () => throw new TransportException('timeout')];
    }
}
