<?php

namespace App\Service\Webservice;

use SoapClient;
use SoapFault;

final class SageX3Client
{
    private SoapClient $soapClient;
    private array $param;

    public function __construct(
        string $wsdlUrl,
        string $login,
        string $password,
        string $poolAlias,
        string $codeLang = 'FRA',
        int $requestConfig = 0
    ) {
        try {
            $this->soapClient = new SoapClient($wsdlUrl, [
                'login'      => $login,
                'password'   => $password,
                'trace'      => true,
                'exceptions' => true,
            ]);

            $this->param = [
                'codeLang'      => $codeLang,
                'poolAlias'     => $poolAlias,
                'requestConfig' => $requestConfig,
            ];
        } catch (SoapFault $e) {
            throw new \RuntimeException('Erreur lors de la connexion SOAP : ' . $e->getMessage());
        }
    }

    public function run(string $webserviceName, string $xmlPayload): mixed
    {
        try {
            return $this->soapClient->run($this->param, $webserviceName, $xmlPayload);
        } catch (SoapFault $e) {
            throw new \RuntimeException('Erreur lors de l\'appel du webservice Sage X3 : ' . $e->getMessage());
        }
    }
}
