<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sends HSTS on the pages rendered by Symfony: on the IONOS server, the header set in public/.htaccess
 * only reaches static files and redirects. Plain HTTP requests never reach PHP (redirected to HTTPS by
 * public/.htaccess), and browsers ignore this header over HTTP anyway.
 */
#[When(env: 'prod')]
#[When(env: 'preprod')]
#[AsEventListener(event: KernelEvents::RESPONSE)]
final class StrictTransportSecurityListener
{
    // Same value as public/.htaccess (no includeSubDomains: other rapkalin.fr sub-domains are not concerned)
    public const string HEADER_VALUE = 'max-age=31536000';

    public function __invoke(ResponseEvent $event): void
    {
        if ($event->isMainRequest()) {
            $event->getResponse()->headers->set('Strict-Transport-Security', self::HEADER_VALUE);
        }
    }
}
