<?php

namespace App\Service\Webservice;

use SoapClient;
use SoapFault;

final class SageX3Client
{
    private ?SoapClient $soapClient = null;
    private array $param;

    /**
     * La connexion SOAP (téléchargement du WSDL) est ouverte à la PREMIÈRE utilisation (run), pas à la construction :
     * ce service est injecté dans des services qui n'appellent pas X3 pour toutes leurs actions (ex. la validation des
     * lignes de l'Import OD, qui ne fait que lire le cube). Sinon chaque requête dépendait de la disponibilité du WSDL
     * Sage X3 — une lenteur ou une panne faisait tomber même la validation (« Failed to fetch »).
     */
    public function __construct(
        private readonly string $wsdlUrl,
        private readonly string $login,
        private readonly string $password,
        string $poolAlias,
        string $codeLang = 'FRA',
        int $requestConfig = 0,
        /** @var array<string,string> pool par environnement X3 (TEST, DEV) ; PROD = $poolAlias */
        private readonly array $poolAliases = [],
    ) {
        $this->param = [
            'codeLang'      => $codeLang,
            'poolAlias'     => $poolAlias,
            'requestConfig' => $requestConfig,
        ];
    }

    private function soapClient(): SoapClient
    {
        if ($this->soapClient === null) {
            try {
                $this->soapClient = new SoapClient($this->wsdlUrl, [
                    'login'      => $this->login,
                    'password'   => $this->password,
                    'trace'      => true,
                    'exceptions' => true,
                ]);
            } catch (SoapFault $e) {
                throw new \RuntimeException('Erreur lors de la connexion SOAP : ' . $e->getMessage());
            }
        }

        return $this->soapClient;
    }

    /**
     * @param string $x3Env PROD (défaut, pool historique), TEST ou DEV. Un environnement sans pool configuré est
     *                      refusé : jamais de repli sur PROD (un flux de test ne doit pas partir en production).
     */
    public function run(string $webserviceName, string $xmlPayload, string $x3Env = 'PROD'): mixed
    {
        $param = $this->param;
        if ($x3Env !== 'PROD') {
            $alias = $this->poolAliases[$x3Env] ?? '';
            if ($alias === '') {
                throw new \RuntimeException(sprintf('Aucun pool X3 configuré pour l\'environnement %s (variable SAGE_X3_POOL_ALIAS_%s).', $x3Env, $x3Env));
            }
            $param['poolAlias'] = $alias;
        }

        try {
            return $this->soapClient()->run($param, $webserviceName, $xmlPayload);
        } catch (SoapFault $e) {
            throw new \RuntimeException('Erreur lors de l\'appel du webservice Sage X3 : ' . $e->getMessage());
        }
    }
}
