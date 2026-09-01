<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\MssqlManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SoaX3Service
{
    private MssqlManager $mssqlLcs;

    public function __construct(
        MssqlManagerFactory $mssqlManagerFactory,
        #[Autowire('%db.lcs%')]
        string $dbLcs,
    ) {
        $this->mssqlLcs = $mssqlManagerFactory->create($dbLcs);
    }

    public function searchClients(string $q): array
    {
        $like = str_replace("'", "''", $q);
        $sql  = "
            SELECT TOP 30
                BPC.BPCNUM_0  AS code,
                BPC.BPCNAM_0  AS nom,
                BPC.CUR_0     AS devise,
                BPR.LAN_0     AS langue,
                BPA.WEB_0     AS email
            FROM X3_LCS.BPCUSTOMER BPC
            INNER JOIN X3_LCS.BPADDRESS BPA ON BPC.BPCNUM_0 = BPA.BPANUM_0 AND BPA.BPAADD_0 = '001'
            INNER JOIN X3_LCS.BPARTNER  BPR ON BPC.BPCNUM_0 = BPR.BPRNUM_0
            WHERE (BPC.BPCNUM_0 LIKE '%{$like}%' OR BPC.BPCNAM_0 LIKE '%{$like}%')
            ORDER BY BPC.BPCNUM_0
        ";
        $rows = $this->mssqlLcs->executeQuery($sql);

        return array_map(fn($r) => [
            'code'   => trim($r->code   ?? ''),
            'nom'    => trim($r->nom    ?? ''),
            'devise' => trim($r->devise ?? 'EUR'),
            'langue' => trim($r->langue ?? ''),
            'email'  => trim($r->email  ?? ''),
        ], $rows);
    }

    public function searchArticles(string $q): array
    {
        $like = str_replace("'", "''", $q);
        $sql  = "
            SELECT TOP 30
                ITM.ITMREF_0    AS code,
                ITM.ITMDES1_0   AS nom,
                ITM.ZMODELCOD_0 AS modele
            FROM X3_LCS.ITMMASTER ITM
            WHERE (ITM.ITMREF_0 LIKE '%{$like}%' OR ITM.ITMDES1_0 LIKE '%{$like}%'
                OR ITM.ITMDES2_0 LIKE '%{$like}%' OR ITM.ITMDES3_0 LIKE '%{$like}%')
            ORDER BY ITM.ITMREF_0
        ";

        $rows = $this->mssqlLcs->executeQuery($sql);

        return array_map(fn($r) => [
            'code'   => trim($r->code   ?? ''),
            'nom'    => trim($r->nom    ?? ''),
            'modele' => trim($r->modele ?? ''),
        ], $rows);
    }

    public function getPrixAchat(string $modele): ?float
    {
        $modele = str_replace("'", "''", $modele);
        $sql    = "
            SELECT TOP 1 SPL.PRI_0 AS prix
            FROM X3_LCS.SPRICLIST AS SPL
            WHERE SPL.PLI_0     = 'T10'
              AND SPL.PLICRI3_0 = '{$modele}'
              AND SPL.PLICRI2_0 = '2026-01-SS'
              AND SPL.PLICRI1_0 = 'WSPEUR'
        ";

        $rows = $this->mssqlLcs->executeQuery($sql);

        if (empty($rows) || $rows[0]->prix === null) {
            return null;
        }

        return (float) $rows[0]->prix;
    }
}
