<?php

namespace App\Tests\Service\X3;

use App\Service\X3\X3Environment;
use App\Service\X3\X3EnvironmentProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Request;

final class X3EnvironmentProviderTest extends TestCase
{
    private function provider(string $appEnv, ?Session $session = null): X3EnvironmentProvider
    {
        $stack = new RequestStack();
        if ($session !== null) {
            $request = new Request();
            $request->setSession($session);
            $stack->push($request);
        }

        return new X3EnvironmentProvider($stack, $appEnv);
    }

    public function testSchemasAreTheThreeKnownOnes(): void
    {
        self::assertSame('X3_LCS', X3Environment::PROD->schema());
        self::assertSame('X3_LCS_TEST', X3Environment::TEST->schema());
        self::assertSame('X3_LCS_DEV', X3Environment::DEV->schema());
    }

    public function testDevCanSwitchAndDefaultsToProd(): void
    {
        $provider = $this->provider('dev', new Session(new MockArraySessionStorage()));

        self::assertTrue($provider->isSwitchable());
        self::assertSame(X3Environment::PROD, $provider->current());
        $provider->set(X3Environment::TEST);
        self::assertSame(X3Environment::TEST, $provider->current());
    }

    public function testOutsideDevItIsAlwaysProdEvenWithAValueInSession(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('x3_environment', 'TEST');

        foreach (['prod', 'preprod', 'test'] as $appEnv) {
            $provider = $this->provider($appEnv, $session);
            self::assertFalse($provider->isSwitchable());
            self::assertSame(X3Environment::PROD, $provider->current(), $appEnv);
        }
    }

    public function testSettingOutsideDevIsRefused(): void
    {
        $this->expectException(\LogicException::class);
        $this->provider('prod', new Session(new MockArraySessionStorage()))->set(X3Environment::TEST);
    }

    public function testUnknownSessionValueOrNoSessionFallsBackToProd(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('x3_environment', 'N_IMPORTE_QUOI');

        self::assertSame(X3Environment::PROD, $this->provider('dev', $session)->current());
        self::assertSame(X3Environment::PROD, $this->provider('dev')->current());
    }
}
