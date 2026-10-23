<?php

namespace App\Tests\Functional;

use App\Constant\UploadConfig;

class PageTest extends AppWebTestCase
{
    public function testHomePageShowsTheUploadRulesAndCounter(): void
    {
        $this->createItem();

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('#counter', 'Already 1 files uploaded!');
        self::assertSelectorTextSame(
            '#card-area-subtitle h2:first-child',
            'Allowed extensions: ' . implode(', ', UploadConfig::allowedExtensions())
        );
        self::assertSelectorTextContains('#card-area-subtitle', 'Max file size: ' . UploadConfig::MAX_FILE_SIZE_GB . 'GB');

        // upload.js reads the upload rules from these attributes
        $dropArea = $crawler->filter('#card-area-drop');
        self::assertSame('/handleFile', $dropArea->attr('data-upload-url'));
        self::assertSame((string) UploadConfig::CHUNK_SIZE, $dropArea->attr('data-chunk-size'));
        self::assertSame((string) UploadConfig::MAX_FILE_SIZE, $dropArea->attr('data-max-file-size'));
        self::assertSame(UploadConfig::ALLOWED_EXTENSIONS, json_decode($dropArea->attr('data-allowed-extensions'), true));
    }

    public function testFooterShowsTheComposerVersionOnEveryPage(): void
    {
        $version = json_decode(file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true)['version'];
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version, 'composer.json version must be semver');

        $item = $this->createItem();
        $this->createAppClient(['debug' => false]);

        foreach (['/', '/download/' . $item->getShowId(), '/download/unknown'] as $url) {
            $this->client->request('GET', $url);
            self::assertSelectorTextSame('#app-footer', "Bigupload v$version");
        }
    }

    public function testIconsAreInlinedWithoutThirdPartyScript(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertCount(1, $crawler->filter('.icon-paper-plane svg'));
        self::assertStringNotContainsString('fontawesome.com/', $crawler->filter('head')->html());
    }

    public function testSecurityHeadersAreSent(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertResponseHeaderSame('X-Frame-Options', 'DENY');
        self::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');

        $csp = $this->client->getResponse()->headers->get('Content-Security-Policy');
        self::assertNotNull($csp, 'The CSP must be sent whatever the host');
        self::assertStringContainsString("default-src 'self'", $csp);
        self::assertStringContainsString("object-src 'none'", $csp);
        self::assertStringContainsString("frame-ancestors 'none'", $csp);
        $directives = $this->parseCsp($csp);
        self::assertStringNotContainsString('data:', $directives['script-src']);
        // Without style-src the browser applies default-src
        self::assertStringNotContainsString("'unsafe-inline'", $directives['style-src'] ?? $directives['default-src']);

        // Inline scripts are allowed through the nonce of the CSP only
        $nonce = $crawler->filter('script[type="importmap"]')->attr('nonce');
        self::assertNotEmpty($nonce);
        self::assertStringContainsString("'nonce-$nonce'", $csp);
    }

    /**
     * @return array<string, string> sources by directive name
     */
    private function parseCsp(string $csp): array
    {
        $directives = [];
        foreach (array_filter(array_map(trim(...), explode(';', $csp))) as $directive) {
            [$name, $sources] = explode(' ', $directive, 2) + [1 => ''];
            $directives[$name] = $sources;
        }

        return $directives;
    }

    public function testNotFoundPageEscapesTheUrl(): void
    {
        $this->createAppClient(['debug' => false]);

        $this->client->request('GET', '/%3Cscript%3Ealert(1)%3C%2Fscript%3E');

        self::assertResponseStatusCodeSame(404);
        self::assertSelectorTextSame('.title-error', 'File not found');
        self::assertStringNotContainsString('<script>alert(1)', $this->client->getResponse()->getContent());
    }

    public function testNotFoundItemPageShowsTheErrorMessage(): void
    {
        $this->createAppClient(['debug' => false]);

        $this->client->request('GET', '/download/unknown');

        self::assertResponseStatusCodeSame(404);
        self::assertSelectorTextContains('.text-error', "It seems that the file you are looking for doesn't exist.");
        self::assertCount(1, $this->client->getCrawler()->filter('.icon-circle-chevron-down svg'));
    }
}
