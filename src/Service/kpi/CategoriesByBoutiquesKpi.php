<?php

namespace App\Service\kpi;

use App\Factory\MssqlManagerFactory;
use App\Repository\KpiDeckPresentationRepository;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\Helpers;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;

class CategoriesByBoutiquesKpi
{
    private MssqlManager $mssqlLcs;


    public function __construct(
        private KpiDeckPresentationRepository $kpiDeckPresentationRepository,
        private RfcByBoutique $rfcByBoutique,
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private Helpers $helpers
    ) {
        $this->mssqlLcs = $this->mssqlManagerFactory->create('lcs');
    }

    public function getDashboardData(): array
    {
        return [
            [
                'name' => "St Germain\n(hors JO)",
                'categories' => [
                    [
                        'name' => 'APPAREL',
                        'code_label' => 'Item Group Code',
                        'items' => [
                            ['code' => 'SWEAT', 'amount_n' => 4502, 'amount_n1' => 5739, 'evolution' => -21.6],
                            ['code' => 'PANTS', 'amount_n' => 1711, 'amount_n1' => 2753, 'evolution' => -37.9],
                            ['code' => 'TEE-SHIRT', 'amount_n' => 1527, 'amount_n1' => 2592, 'evolution' => -41.1],
                            ['code' => 'POLO', 'amount_n' => 1288, 'amount_n1' => 1336, 'evolution' => -3.6],
                            ['code' => 'JACKET', 'amount_n' => 1052, 'amount_n1' => 714, 'evolution' => 47.4],
                            ['code' => 'SHORT', 'amount_n' => 202, 'amount_n1' => 520, 'evolution' => -61.1],
                            ['code' => 'DRESS', 'amount_n' => 42, 'amount_n1' => null, 'evolution' => 'Infini'],
                            ['code' => 'TANK', 'amount_n' => 25, 'amount_n1' => null, 'evolution' => 'Infini'],
                        ],
                        'total' => ['amount_n' => 10348, 'amount_n1' => 13653, 'evolution' => -24.2],
                        'lw' => 7604,
                    ],
                    [
                        'name' => 'FOOTWEAR',
                        'code_label' => 'Gender Code',
                        'items' => [
                            ['code' => 'MALE', 'amount_n' => 3423, 'amount_n1' => 2309, 'evolution' => 48.2],
                            ['code' => 'UNISEX', 'amount_n' => 2068, 'amount_n1' => 913, 'evolution' => 126.5],
                            ['code' => 'FEMALE', 'amount_n' => 932, 'amount_n1' => 443, 'evolution' => 110.3],
                        ],
                        'total' => ['amount_n' => 6424, 'amount_n1' => 3666, 'evolution' => 75.2],
                        'lw' => 3601,
                    ],
                ],
            ],
            [
                'name' => "Citadium\n(Hors JO)",
                'categories' => [
                    [
                        'name' => 'APPAREL',
                        'code_label' => 'Item Group Code',
                        'items' => [
                            ['code' => 'SWEAT', 'amount_n' => 473, 'amount_n1' => 1450, 'evolution' => -67.4],
                            ['code' => 'PANTS', 'amount_n' => 399, 'amount_n1' => 695, 'evolution' => -42.5],
                            ['code' => 'JACKET', 'amount_n' => 208, 'amount_n1' => 561, 'evolution' => -62.9],
                            ['code' => 'TEE-SHIRT', 'amount_n' => 208, 'amount_n1' => 844, 'evolution' => -75.4],
                            ['code' => 'POLO', 'amount_n' => 85, 'amount_n1' => 800, 'evolution' => -89.4],
                            ['code' => 'SHORT', 'amount_n' => 37, 'amount_n1' => 297, 'evolution' => -87.5],
                        ],
                        'total' => ['amount_n' => 1410, 'amount_n1' => 4647, 'evolution' => -69.7],
                        'lw' => 1203,
                    ],
                    [
                        'name' => 'FOOTWEAR',
                        'code_label' => 'Gender Code',
                        'items' => [
                            ['code' => 'MALE', 'amount_n' => 858, 'amount_n1' => 2085, 'evolution' => -58.9],
                            ['code' => 'UNISEX', 'amount_n' => 659, 'amount_n1' => 553, 'evolution' => 19.1],
                            ['code' => 'FEMALE', 'amount_n' => 377, 'amount_n1' => 337, 'evolution' => 12.0],
                        ],
                        'total' => ['amount_n' => 1894, 'amount_n1' => 2975, 'evolution' => -36.3],
                        'lw' => 1141,
                    ],
                ],
            ],
            [
                'name' => "Retail Affiliés\n(hors JO)",
                'categories' => [
                    [
                        'name' => 'APPAREL',
                        'code_label' => 'Item Group Code',
                        'items' => [
                            ['code' => 'SWEAT', 'amount_n' => 3014, 'amount_n1' => 5884, 'evolution' => -48.8],
                            ['code' => 'PANTS', 'amount_n' => 1684, 'amount_n1' => 3116, 'evolution' => -46.0],
                            ['code' => 'TEE-SHIRT', 'amount_n' => 1639, 'amount_n1' => 2286, 'evolution' => -28.3],
                            ['code' => 'POLO', 'amount_n' => 1009, 'amount_n1' => 950, 'evolution' => 6.2],
                            ['code' => 'JACKET', 'amount_n' => 1004, 'amount_n1' => 1200, 'evolution' => -16.3],
                            ['code' => 'SHORT', 'amount_n' => 78, 'amount_n1' => 324, 'evolution' => -75.8],
                        ],
                        'total' => ['amount_n' => 8428, 'amount_n1' => 13760, 'evolution' => -38.8],
                        'lw' => 5391,
                    ],
                    [
                        'name' => 'FOOTWEAR',
                        'code_label' => 'Gender Code',
                        'items' => [
                            ['code' => 'MALE', 'amount_n' => 1790, 'amount_n1' => 3040, 'evolution' => -41.1],
                            ['code' => 'UNISEX', 'amount_n' => 653, 'amount_n1' => 723, 'evolution' => -9.7],
                            ['code' => 'FEMALE', 'amount_n' => 491, 'amount_n1' => 1119, 'evolution' => -56.1],
                        ],
                        'total' => ['amount_n' => 2935, 'amount_n1' => 4883, 'evolution' => -39.9],
                        'lw' => 1524,
                    ],
                ],
            ],
        ];
    }
}
