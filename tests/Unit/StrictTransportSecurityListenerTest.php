<?php

namespace App\Tests\Unit;

use App\EventListener\StrictTransportSecurityListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class StrictTransportSecurityListenerTest extends TestCase
{
    public function testHeaderIsAddedToThePages(): void
    {
        $event = $this->createEvent(HttpKernelInterface::MAIN_REQUEST);

        (new StrictTransportSecurityListener())($event);

        self::assertSame('max-age=31536000', $event->getResponse()->headers->get('Strict-Transport-Security'));
    }

    public function testSubRequestsAreIgnored(): void
    {
        $event = $this->createEvent(HttpKernelInterface::SUB_REQUEST);

        (new StrictTransportSecurityListener())($event);

        self::assertFalse($event->getResponse()->headers->has('Strict-Transport-Security'));
    }

    /**
     * Must stay aligned with the header sent by Apache on the static files
     */
    public function testSameValueAsTheHtaccess(): void
    {
        self::assertStringContainsString(
            'Header always set Strict-Transport-Security "' . StrictTransportSecurityListener::HEADER_VALUE . '"',
            file_get_contents(dirname(__DIR__, 2) . '/public/.htaccess')
        );
    }

    private function createEvent(int $requestType): ResponseEvent
    {
        return new ResponseEvent($this->createStub(HttpKernelInterface::class), new Request(), $requestType, new Response());
    }
}
