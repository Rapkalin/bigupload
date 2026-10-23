<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UrlShortenerService
{
    private const string API_URL = 'https://tinyurl.com/api-create.php';
    private const int TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Returns the tinyurl.com short link, or the original url if tinyurl is unavailable.
     */
    public function shorten(string $url): string
    {
        try {
            $shortUrl = $this->httpClient->request('GET', self::API_URL, [
                'query' => ['url' => $url],
                'timeout' => self::TIMEOUT_SECONDS,
                'max_duration' => self::TIMEOUT_SECONDS,
            ])->getContent();

            if (filter_var($shortUrl, FILTER_VALIDATE_URL)) {
                return $shortUrl;
            }

            $this->logger->error('UrlShortenerService: unexpected tinyurl response', ['response' => $shortUrl]);
        } catch (\Throwable $e) {
            $this->logger->error("UrlShortenerService: {$e->getMessage()}");
        }

        return $url;
    }
}
