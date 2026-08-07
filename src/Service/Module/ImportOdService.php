<?php

namespace App\Service\Module;

use App\Entity\SalesWebService;
use App\Factory\MssqlManagerFactory;
use App\Service\Tools\MssqlManager;
use App\Service\Webservice\WebServiceDispatcher;
use App\Service\Webservice\XmlBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ImportOdService
{
    private MssqlManager $mssqlLcs;

    public function __construct(
        private WebServiceDispatcher $dispatcher,
        MssqlManagerFactory $mssqlManagerFactory,
        #[Autowire('%db.lcs%')]
        string $dbLcs,
    ) {
        $this->mssqlLcs = $mssqlManagerFactory->create($dbLcs);
    }

    public function generate(array $entete, array $lignes): SalesWebService
    {
        $xml = XmlBuilder::buildOD($entete, $lignes);
        return $this->dispatcher->dispatch('ZWSIMPOD', $xml);
    }

    /**
     * Valide les axes analytiques (CACCE) pour un lot de lignes.
     * Retourne la liste des valeurs invalides pour chaque champ validé.
     */
    public function validateLignes(array $lignes): array
    {
        $errors = [];

        // Collecte les valeurs uniques d'Axe1 non vides
        $axe1Values = array_values(array_unique(array_filter(
            array_column($lignes, 'Axe1'),
            fn($v) => $v !== '' && $v !== null
        )));

        if (!empty($axe1Values)) {
            $placeholders = implode(',', array_map(fn($i) => ':axe1_' . $i, array_keys($axe1Values)));
            $params = [];
            foreach ($axe1Values as $i => $v) {
                $params['axe1_' . $i] = $v;
            }

            $sql = "SELECT CAE.CCE_0 FROM X3_LCS.CACCE CAE WHERE CAE.CCE_0 IN ($placeholders)";
            $found = array_column($this->mssqlLcs->executeQueryWithParams($sql, $params), 'CCE_0');

            $invalid = array_values(array_diff($axe1Values, $found));
            if (!empty($invalid)) {
                $errors['Axe1'] = $invalid;
            }
        }

        return $errors;
    }
}
