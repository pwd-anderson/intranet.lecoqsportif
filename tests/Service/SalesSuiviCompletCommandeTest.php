<?php

namespace App\Tests\Service;

use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Sales;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\MssqlManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/** Suivi complet de commande : requête X3 + historique Navision, plafond de lignes, périodes autorisées. Base simulée, X3 jamais interrogé. */
final class SalesSuiviCompletCommandeTest extends TestCase
{
    private MssqlManager&MockObject $db;
    private ?string $sentQuery = null;
    /** @var array<string,mixed> */
    private array $sentParams = [];

    protected function setUp(): void
    {
        $this->sentQuery = null;
        $this->sentParams = [];
        $this->db = $this->createMock(MssqlManager::class);
    }

    /** @param list<object> $rows */
    private function sales(array $rows = []): Sales
    {
        $this->db->method('executeQueryWithParams')->willReturnCallback(function (string $q, array $p) use ($rows): array {
            $this->sentQuery = $q;
            $this->sentParams = $p;

            return $rows;
        });

        $sales = (new \ReflectionClass(Sales::class))->newInstanceWithoutConstructor();
        foreach ([
            'mssqlSeiInstance' => $this->db,
            'sqlFileLoader' => new SqlFileLoader(dirname(__DIR__, 2)),
            'logger' => new NullLogger(),
            'graphMailer' => $this->createStub(GraphMailer::class),
        ] as $prop => $value) {
            $r = new \ReflectionProperty(Sales::class, $prop);
            $r->setValue($sales, $value);
        }

        return $sales;
    }

    public function testTheQueryReadsBothX3AndTheNavisionHistory(): void
    {
        $this->sales()->getSuiviCompletCommande(['NUM_FACTURE' => 'FVB26008024']);

        self::assertStringContainsString('PRODXXX_SUIVI_COMPLET_COMMANDE', $this->sentQuery, 'source X3');
        self::assertStringContainsString("'0.Nav_Histo'", $this->sentQuery, 'historique Navision');
        self::assertStringContainsString('UNION ALL', $this->sentQuery);
        self::assertStringContainsString('WHERE [NUM_FACTURE] = :num_facture', $this->sentQuery);
        self::assertSame(['num_facture' => 'FVB26008024'], $this->sentParams, 'la valeur saisie est liée, jamais concaténée');
        self::assertStringNotContainsString('FVB26008024', $this->sentQuery);
        self::assertStringNotContainsString('{{', $this->sentQuery, 'tous les emplacements sont remplacés');
    }

    public function testNothingIsQueriedWithoutAnyCriterion(): void
    {
        self::assertSame([], $this->sales()->getSuiviCompletCommande([]));
        self::assertNull($this->sentQuery);
    }

    public function testLongPeriodsUpTo30MonthsAreAcceptedAndOthersIgnored(): void
    {
        $sales = $this->sales();

        $sales->getSuiviCompletCommande([], ['type' => 'months', 'value' => 30]);
        self::assertStringContainsString('[DATE_CREATION_BP] >= :period_from', (string) $this->sentQuery);
        $from = new \DateTimeImmutable($this->sentParams['period_from']);
        self::assertEqualsWithDelta((new \DateTimeImmutable('-30 months'))->getTimestamp(), $from->getTimestamp(), 5);

        $this->sentQuery = null;
        $sales->getSuiviCompletCommande([], ['type' => 'months', 'value' => 7]);
        self::assertNull($this->sentQuery, 'une durée hors liste n\'interroge rien');
    }

    public function testResultIsCutAtTheCapAndFlaggedAsTruncated(): void
    {
        $rows = array_fill(0, Sales::SUIVI_COMPLET_MAX_ROWS + 1, (object) ['NUM_COMMANDE' => 'X']);

        $result = $this->sales($rows)->getSuiviCompletCommande([], ['type' => 'months', 'value' => 30], $truncated);

        self::assertCount(Sales::SUIVI_COMPLET_MAX_ROWS, $result);
        self::assertTrue($truncated);
        self::assertStringContainsString('TOP (' . (Sales::SUIVI_COMPLET_MAX_ROWS + 1) . ')', $this->sentQuery, 'une ligne de plus que le plafond est lue pour détecter la coupure');
    }

    public function testAResultUnderTheCapIsReturnedWholeAndNotFlagged(): void
    {
        $rows = array_fill(0, 120, (object) ['NUM_COMMANDE' => 'X']);

        $result = $this->sales($rows)->getSuiviCompletCommande(['NUM_COMMANDE' => 'X'], null, $truncated);

        self::assertCount(120, $result);
        self::assertFalse($truncated);
    }

    public function testExactlyTheCapIsNotTruncated(): void
    {
        $rows = array_fill(0, Sales::SUIVI_COMPLET_MAX_ROWS, (object) ['NUM_COMMANDE' => 'X']);

        $this->sales($rows)->getSuiviCompletCommande([], ['type' => 'year', 'value' => 2025], $truncated);

        self::assertFalse($truncated);
    }

    public function testCsvExportIsNotCappedAndFormatsValues(): void
    {
        $this->db->method('iterateQueryWithParams')->willReturnCallback(function (string $q, array $p): \Generator {
            $this->sentQuery = $q;
            $this->sentParams = $p;
            yield ['NUM_COMMANDE' => 'A1', 'MONTANT' => '12.5', 'QTE' => '3.0'];
            yield ['NUM_COMMANDE' => 'A2', 'MONTANT' => null, 'QTE' => '4'];
        });
        $sales = $this->sales();

        $out = fopen('php://memory', 'w+');
        $n = $sales->writeSuiviCompletCommandeCsv(
            $out,
            ['NUM_COMMANDE' => 'A1'],
            null,
            ['NUM_COMMANDE' => ['header' => 'N° Commande', 'type' => 'string'], 'MONTANT' => ['header' => 'Montant', 'type' => 'decimal'], 'QTE' => ['header' => 'Qté', 'type' => 'integer']]
        );
        rewind($out);
        $csv = stream_get_contents($out);

        self::assertSame(2, $n);
        self::assertStringContainsString('TOP (2147483647)', $this->sentQuery, 'aucun plafond de lignes');
        self::assertSame(['num_commande' => 'A1'], $this->sentParams);
        self::assertStringStartsWith("\xEF\xBB\xBFsep=;\r\n", $csv);
        self::assertStringContainsString("\"N° Commande\";Montant;Qté\n", $csv);
        self::assertStringContainsString("A1;12,50;3\n", $csv);
        self::assertStringContainsString("A2;;4\n", $csv);
    }

    public function testCsvExportWithoutCriterionWritesNothing(): void
    {
        $out = fopen('php://memory', 'w+');
        self::assertSame(0, $this->sales()->writeSuiviCompletCommandeCsv($out, [], null, ['X' => ['header' => 'X', 'type' => 'string']]));
        rewind($out);
        self::assertSame('', stream_get_contents($out));
    }

    public function testBonPrepaDetailBindsTheNumberAndIgnoresEmpty(): void
    {
        $sales = $this->sales([['N_COLIS' => 'C1']]);

        self::assertSame([], $sales->getBonPrepaDetail('  '));
        self::assertNull($this->sentQuery, 'aucun appel base pour un numéro vide');

        self::assertSame([['N_COLIS' => 'C1']], $sales->getBonPrepaDetail('BPLCSI26-00023978'));
        self::assertSame(['num_bon_prepa' => 'BPLCSI26-00023978', 'num_bon_prepa_lignes' => 'BPLCSI26-00023978'], $this->sentParams);
        self::assertStringContainsString('FROM X3_LCS.SPACKD SPD', $this->sentQuery, 'SPACKD est la table principale');
        self::assertStringContainsString('PRH.PRHNUM_0 = :num_bon_prepa', $this->sentQuery);
    }
}
