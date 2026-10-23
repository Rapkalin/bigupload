<?php

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Replaces every HTTP call in the test env (tinyurl.com is never reached by the tests).
 */
final class TinyUrlMockResponseFactory
{
    public const string SHORT_URL = 'https://tinyurl.com/bigupload-test';

    public function __invoke(): MockResponse
    {
        return new MockResponse(self::SHORT_URL);
    }
}
