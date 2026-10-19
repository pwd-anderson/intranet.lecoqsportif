<?php

namespace App\Tests\Service\Webservice;

use App\Service\Webservice\SageX3Client;
use PHPUnit\Framework\TestCase;

final class SageX3ClientTest extends TestCase
{
    public function testAnEnvironmentWithoutPoolIsRefusedAndNeverFallsBackToProd(): void
    {
        $client = new SageX3Client('http://invalide.test/wsdl', 'u', 'p', 'POOLPROD', 'FRA', 0, ['TEST' => '', 'DEV' => 'POOLDEV']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SAGE_X3_POOL_ALIAS_TEST');
        $client->run('WSCRESPL', '<xml/>', 'TEST'); // refusé avant toute connexion SOAP
    }
}
