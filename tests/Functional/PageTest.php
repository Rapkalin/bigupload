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
            '#card-area-subtitle p:first-child',
            'Allowed extensions: ' . implode(', ', UploadConfig::allowedExtensions())
        );
        self::assertSelectorTextContains('#card-area-subtitle', 'Max file size: ' . UploadConfig::MAX_FILE_SIZE_GB . 'GB');

        // upload.js reads the upload rules from these attributes
        $dropArea = $crawler->filter('#card-area-drop');
        self::assertSame('/handleFile', $dropArea->attr('data-upload-url'));
        self::assertSame((string) UploadConfig::MAX_FILE_NAME_BYTES, $dropArea->attr('data-max-file-name-bytes'));
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

        self::assertResponseHeaderSame('Cross-Origin-Opener-Policy', 'same-origin');
        self::assertResponseHeaderSame('Permissions-Policy', 'browsing-topics=(), camera=(), geolocation=(), microphone=(), payment=(), usb=()');
        // Legacy IE header not sent
        self::assertResponseNotHasHeader('X-Content-Security-Policy');

        self::assertResponseHeaderSame('X-Frame-Options', 'DENY');
        self::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');

        $csp = $this->client->getResponse()->headers->get('Content-Security-Policy');
        self::assertNotNull($csp, 'The CSP must be sent whatever the host');
        self::assertStringContainsString("default-src 'self'", $csp);
        self::assertStringContainsString("object-src 'none'", $csp);
        self::assertStringContainsString("frame-ancestors 'none'", $csp);
        $directives = $this->parseCsp($csp);
        // Scripts of the site only: no inline script ('unsafe-inline'), no CDN (the importmap polyfill used ga.jspm.io).
        // script-src identical to default-src is not sent: the browser then applies default-src
        self::assertSame("'self'", $directives['script-src'] ?? $directives['default-src']);
        // Without style-src the browser applies default-src
        self::assertStringNotContainsString("'unsafe-inline'", $directives['style-src'] ?? $directives['default-src']);

        foreach ($crawler->filter('script') as $script) {
            self::assertNotEmpty($script->getAttribute('src'), 'Inline scripts are refused by the CSP');
        }
    }

    /**
     * Regression: Stimulus and Turbo (9 JS modules, ~47 KB gzip) were loaded on every page without being used
     */
    public function testOnlyThePageScriptsAreLoaded(): void
    {
        $item = $this->createItem();

        $crawler = $this->client->request('GET', '/');
        self::assertSame(
            ['plupload.full.min', 'upload', 'button_animation', 'copy_to_clipboard'],
            $crawler->filter('script')->each(static fn ($script): string => preg_replace('/-[\w-]{7}\.js$/', '', basename($script->attr('src'))))
        );
        self::assertCount(0, $crawler->filter('link[rel="modulepreload"], script[type="importmap"]'));

        $crawler = $this->client->request('GET', '/download/' . $item->getShowId());
        self::assertCount(1, $crawler->filter('script'));
    }

    /**
     * Regression: app.css loaded the 8 other stylesheets with @import, one after the other
     */
    public function testStylesheetsAreLinkedWithoutImport(): void
    {
        $crawler = $this->client->request('GET', '/');

        $stylesheets = $crawler->filter('link[rel="stylesheet"]');
        self::assertCount(count(glob(dirname(__DIR__, 2) . '/assets/styles/*{,/*}.css', GLOB_BRACE)), $stylesheets);
        foreach (glob(dirname(__DIR__, 2) . '/assets/styles/*{,/*}.css', GLOB_BRACE) as $file) {
            self::assertStringNotContainsString('@import', file_get_contents($file), $file);
        }
    }

    public function testHomePageIsDescribedForTheSearchEngines(): void
    {
        $crawler = $this->client->request('GET', '/');

        $title = 'Send large files up to ' . UploadConfig::MAX_FILE_SIZE_GB . ' GB - Bigupload';
        self::assertPageTitleSame($title);
        self::assertSelectorExists('meta[property="og:title"][content="' . $title . '"]');
        self::assertStringStartsWith('Send large files up to 15 GB', $crawler->filter('meta[name="description"]')->attr('content'));
        self::assertSame('http://localhost/', $crawler->filter('link[rel="canonical"]')->attr('href'));
        self::assertSelectorNotExists('meta[name="robots"]');
    }

    public function testRobotsAndSitemapPointToTheProductionSite(): void
    {
        $publicDir = dirname(__DIR__, 2) . '/public';
        $sitemap = simplexml_load_file($publicDir . '/sitemap.xml');
        $homeUrl = (string) $sitemap->url[0]->loc;

        self::assertSame('https://bigupload.rapkalin.fr/', $homeUrl);
        $robots = file_get_contents($publicDir . '/robots.txt');
        self::assertStringContainsString('Sitemap: ' . $homeUrl . 'sitemap.xml', $robots);
        self::assertStringContainsString('Disallow: /downloadFile/', $robots);
    }

    /**
     * RGAA: one <main>, one h1 placed before the other headings, buttons instead of javascript: links,
     * named form fields and status messages read by screen readers
     */
    public function testUploadPageAccessibility(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertCount(1, $crawler->filter('main'));
        self::assertSame(['h1', 'h2', 'h2'], $crawler->filter('main h1, main h2')->each(static fn ($heading): string => $heading->nodeName()));
        self::assertCount(0, $crawler->filter('a[href^="javascript:"], label'));
        foreach (['browse-files-btn', 'upload-files', 'animatedButton'] as $id) {
            self::assertSame('button', $crawler->filter("#$id")->attr('type'), $id);
        }
        self::assertSame('Download link of your file', $crawler->filter('#downloadLink')->attr('aria-label'));
        self::assertSame('polite', $crawler->filter('#card-area-title-drop')->attr('aria-live'));
        self::assertSame('polite', $crawler->filter('#card-area-title-upload')->attr('aria-live'));
        self::assertSame('status', $crawler->filter('#copy-status')->attr('role'));
    }

    public function testDownloadPageAccessibility(): void
    {
        $item = $this->createItem();

        $crawler = $this->client->request('GET', '/download/' . $item->getShowId());

        self::assertCount(1, $crawler->filter('main'));
        self::assertSame(['h1', 'h2'], $crawler->filter('main h1, main h2')->each(static fn ($heading): string => $heading->nodeName()));
        self::assertCount(0, $crawler->filter('label'));
    }

    public function testHomePageOnlyAnswersGet(): void
    {
        $this->client->request('POST', '/');

        self::assertResponseStatusCodeSame(405);
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
        self::assertPageTitleSame('File not found - Bigupload');
        self::assertSelectorExists('main h1');
        self::assertSelectorExists('meta[name="robots"][content="noindex"]');
        self::assertSelectorTextContains('.text-error', "It seems that the file you are looking for doesn't exist.");
        self::assertCount(1, $this->client->getCrawler()->filter('.icon-circle-chevron-down svg'));
    }
}
