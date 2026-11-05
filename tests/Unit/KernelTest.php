<?php

namespace App\Tests\Unit;

use App\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    /**
     * preprod is the APP_ENV of the preprod server: refusing it would break the preprod site
     */
    #[DataProvider('environmentProvider')]
    public function testServerEnvironmentsAreAllowed(string $environment): void
    {
        $kernel = new Kernel($environment, false);

        self::assertArrayHasKey('kernel.environment', (new \ReflectionMethod($kernel, 'getKernelParameters'))->invoke($kernel));
    }

    public static function environmentProvider(): iterable
    {
        yield ['prod'];
        yield ['preprod'];
    }

    public function testUnknownEnvironmentIsRefused(): void
    {
        $kernel = new Kernel('typo', false);

        $this->expectException(\InvalidArgumentException::class);
        (new \ReflectionMethod($kernel, 'getKernelParameters'))->invoke($kernel);
    }
}
