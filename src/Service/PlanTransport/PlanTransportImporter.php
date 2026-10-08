<?php

namespace App\Service\PlanTransport;

use App\Service\Tools\GraphMailer;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PDO;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Import du plan transport prévision (fichier Excel PURCHASING, onglet « SS27 LIVRAISONS - ARTICLES »)
 * dans MASTER_TABLES.PLAN_TRANSPORT_PREVISION (SEI Cube), puis lecture pour la stat Achats
 * (fetchAll / getLastImport : la table et la connexion sont partagées, pas de second service).
 *
 * Remplacement complet à chaque import (aucune clé naturelle : un même PO / article peut apparaître
 * plusieurs fois en cas de fractionnement) et dans UNE transaction : si quoi que ce soit échoue, la table
 * garde sa version précédente. Les données sont importées telles quelles (transport non normalisé).
 *
 * DDL : Sei/create_table_plan_transport_prevision.sql (à exécuter en dev ET en prod).
 */
class PlanTransportImporter
{
    public const DEFAULT_SHEET = 'SS27 LIVRAISONS - ARTICLES';

    /** Ligne des en-têtes et première ligne de données dans l'onglet. */
    private const HEADER_ROW = 3;
    private const FIRST_DATA_ROW = 4;

    /** En-têtes attendus des colonnes B à J (la cellule A3 contient la légende des couleurs, pas un titre). */
    private const EXPECTED_HEADERS = [
        'B' => 'N° PO',
        'C' => 'Article (SKU)',
        'D' => 'Désignation',
        'E' => 'Qté (pcs)',
        'F' => 'Moyen de transport',
        'G' => 'Départ usine (XF)',
        'H' => 'ETD',
        'I' => 'ETA France (Logtex)',
        'J' => 'Statut départ',
    ];

    private const COLUMNS = [
        'FOURNISSEUR', 'NUM_PO', 'ARTICLE_SKU', 'ARTICLE', 'VARIANT', 'DESIGNATION', 'QTE', 'MOYEN_TRANSPORT',
        'DATE_XF', 'DATE_ETD', 'DATE_ETA_FRANCE', 'STATUT_DEPART', 'IMPORTED_AT', 'SOURCE_FILE', 'SOURCE_MODIFIED',
    ];

    /** 15 paramètres par ligne : 100 lignes par INSERT restent sous la limite de 2 100 paramètres de SQL Server. */
    private const BATCH_SIZE = 100;

    private ?PDO $pdo = null;
    private bool $isDev;

    /** @var array{dsn:string,user:string,password:string} */
    private array $connection;

    /**
     * @param array<string,array{dsn:string,user:string,password:string}> $connections paramètre mssql.connections
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly GraphMailer $graphMailer,
        #[Autowire('%mssql.connections%')]
        array $connections,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
        #[Autowire('%kernel.environment%')]
        string $environment,
    ) {
        $this->connection = $connections[$dbLcsSei] ?? throw new \InvalidArgumentException("Connexion MSSQL inconnue : '$dbLcsSei'");
        $this->isDev = ($environment === 'dev');
    }

    /**
     * Connexion PDO dédiée, en UTF-8 : le pilote dblib (FreeTDS) travaille par défaut en ISO-8859-1, ce qui
     * déformait à l'écriture « É » et le tiret long « — » du statut « BOOKÉ — ON HOLD » (stockés « BOOKÃ? â?? »)
     * et les relisait en « ? ». Avec charset=UTF-8, écriture et lecture sont exactes (vérifié en direct).
     * PDO lève de vraies exceptions (contrairement à MssqlManager qui les avale), ce qui garantit le rollback.
     */
    private function pdo(): PDO
    {
        if ($this->pdo === null) {
            $dsn = $this->connection['dsn'];
            if (str_starts_with($dsn, 'dblib:') && stripos($dsn, 'charset=') === false) {
                $dsn = rtrim($dsn, ';') . ';charset=UTF-8';
            }
            $this->pdo = new PDO($dsn, $this->connection['user'], $this->connection['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }

        return $this->pdo;
    }

    public function table(): string
    {
        return 'MASTER_TABLES.PLAN_TRANSPORT_PREVISION' . ($this->isDev ? '_DEV' : '');
    }

    /**
     * Toutes les lignes de la table pour la stat (≈ 3 300 lignes : chargement direct, filtres/tri côté navigateur).
     * Dates en varchar(10) format 23 ; pas d'ORDER BY, la grille trie (par ETA France par défaut).
     */
    public function fetchAll(): array
    {
        $table = $this->table();

        try {
            return $this->pdo()->query("
                SELECT FOURNISSEUR, NUM_PO, ARTICLE_SKU, ARTICLE, VARIANT, DESIGNATION, QTE, MOYEN_TRANSPORT,
                       CONVERT(varchar(10), DATE_XF, 23)         AS DATE_XF,
                       CONVERT(varchar(10), DATE_ETD, 23)        AS DATE_ETD,
                       CONVERT(varchar(10), DATE_ETA_FRANCE, 23) AS DATE_ETA_FRANCE,
                       STATUT_DEPART
                FROM {$table}
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Achats : Plan transport prévision', $e);
            $this->logger->error('LCS Erreur Achats : Plan transport prévision', ['exception' => $e]);

            return [];
        }
    }

    /**
     * Dernier import (affiché au-dessus de la stat) ou null si la table est vide / illisible.
     *
     * @return array{importedAt:string,sourceFile:?string,sourceModified:?string}|null
     */
    public function getLastImport(): ?array
    {
        $table = $this->table();

        try {
            $rows = $this->pdo()->query("
                SELECT CONVERT(varchar(16), MAX(IMPORTED_AT), 120)     AS IMPORTED_AT,
                       MAX(SOURCE_FILE)                                AS SOURCE_FILE,
                       CONVERT(varchar(16), MAX(SOURCE_MODIFIED), 120) AS SOURCE_MODIFIED
                FROM {$table}
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $this->logger->error('LCS Erreur Achats : dernier import plan transport', ['exception' => $e]);

            return null;
        }

        $row = $rows[0] ?? [];
        if (empty($row['IMPORTED_AT'])) {
            return null;
        }

        return [
            'importedAt' => (string) $row['IMPORTED_AT'],
            'sourceFile' => $row['SOURCE_FILE'] ?? null,
            'sourceModified' => $row['SOURCE_MODIFIED'] ?? null,
        ];
    }

    /**
     * Lit l'onglet, contrôle sa structure et renvoie les lignes prêtes à insérer (sans rien écrire en base).
     *
     * @return array{rows: list<array<string,mixed>>, skipped: int}
     */
    public function parse(string $xlsxPath, string $sheetName = self::DEFAULT_SHEET): array
    {
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$sheetName]);

        try {
            $sheet = $reader->load($xlsxPath)->getActiveSheet();
        } catch (\Throwable $e) {
            throw new \RuntimeException("Onglet « $sheetName » introuvable ou fichier illisible : " . $e->getMessage(), 0, $e);
        }
        if ($sheet->getTitle() !== $sheetName) {
            throw new \RuntimeException("Onglet « $sheetName » introuvable dans le fichier.");
        }

        // Une colonne renommée ou déplacée doit faire échouer l'import (et alerter), jamais décaler les données.
        foreach (self::EXPECTED_HEADERS as $col => $expected) {
            $actual = trim((string) $sheet->getCell($col . self::HEADER_ROW)->getValue());
            if (mb_strtolower($actual) !== mb_strtolower($expected)) {
                throw new \RuntimeException(sprintf(
                    'Structure de l\'onglet modifiée : colonne %s, en-tête attendu « %s », trouvé « %s ».',
                    $col,
                    $expected,
                    $actual
                ));
            }
        }

        $last = $sheet->getHighestDataRow();
        $raw = $sheet->rangeToArray('A' . self::FIRST_DATA_ROW . ':J' . $last, null, false, false, false);

        $rows = [];
        $skipped = 0;
        foreach ($raw as $i => $r) {
            $line = $i + self::FIRST_DATA_ROW;
            $po = $this->text($r[1]);
            $sku = $this->text($r[2]);

            // Lignes vides, ligne TOTAL (sans PO ni article) : ignorées.
            if ($po === null && $sku === null) {
                ++$skipped;
                continue;
            }
            if ($po === null || $sku === null) {
                throw new \RuntimeException("Ligne $line : N° PO ou article manquant.");
            }

            [$article, $variant] = array_pad(explode('_', $sku, 2), 2, null);

            $rows[] = [
                'FOURNISSEUR' => $this->text($r[0]),
                'NUM_PO' => $po,
                'ARTICLE_SKU' => $sku,
                'ARTICLE' => $article,
                'VARIANT' => $variant,
                'DESIGNATION' => $this->text($r[3]),
                'QTE' => $this->integer($r[4], $line, 'Qté'),
                'MOYEN_TRANSPORT' => $this->text($r[5]),
                'DATE_XF' => $this->date($r[6], $line, 'Départ usine (XF)'),
                'DATE_ETD' => $this->date($r[7], $line, 'ETD'),
                'DATE_ETA_FRANCE' => $this->date($r[8], $line, 'ETA France'),
                'STATUT_DEPART' => $this->text($r[9]),
            ];
        }

        return ['rows' => $rows, 'skipped' => $skipped];
    }

    /**
     * Remplace tout le contenu de la table par les lignes données, dans une transaction.
     *
     * @param list<array<string,mixed>> $rows
     *
     * @return int nombre de lignes insérées
     */
    public function replaceAll(array $rows, string $sourceFile, ?string $sourceModified): int
    {
        if ($rows === []) {
            // Garde-fou : un fichier vide ou mal lu ne doit jamais vider la table.
            throw new \RuntimeException('Aucune ligne à importer : la table n\'est pas modifiée.');
        }

        $pdo = $this->pdo();

        $table = $this->table();
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $modified = $sourceModified !== null && $sourceModified !== ''
            ? (new \DateTimeImmutable($sourceModified))->setTimezone(new \DateTimeZone('Europe/Paris'))->format('Y-m-d H:i:s')
            : null;

        $columnList = implode(', ', self::COLUMNS);
        $one = '(' . implode(', ', array_fill(0, count(self::COLUMNS), '?')) . ')';

        $pdo->beginTransaction();
        try {
            $pdo->exec("DELETE FROM {$table}");

            foreach (array_chunk($rows, self::BATCH_SIZE) as $chunk) {
                $stmt = $pdo->prepare("INSERT INTO {$table} ({$columnList}) VALUES " . implode(', ', array_fill(0, count($chunk), $one)));
                $params = [];
                foreach ($chunk as $row) {
                    foreach (['FOURNISSEUR', 'NUM_PO', 'ARTICLE_SKU', 'ARTICLE', 'VARIANT', 'DESIGNATION', 'QTE', 'MOYEN_TRANSPORT', 'DATE_XF', 'DATE_ETD', 'DATE_ETA_FRANCE', 'STATUT_DEPART'] as $col) {
                        $params[] = $row[$col];
                    }
                    array_push($params, $now, $sourceFile, $modified);
                }
                $stmt->execute($params);
            }

            $inserted = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            if ($inserted !== count($rows)) {
                throw new \RuntimeException(sprintf('Contrôle après insertion : %d lignes en table pour %d attendues.', $inserted, count($rows)));
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->logger->error('Import plan transport : échec, table inchangée', ['exception' => $e]);
            throw $e;
        }

        return $inserted;
    }

    private function text(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : $s;
    }

    private function integer(mixed $v, int $line, string $label): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (!is_numeric($v)) {
            throw new \RuntimeException("Ligne $line : « $label » non numérique ($v).");
        }

        return (int) round((float) $v);
    }

    private function date(mixed $v, int $line, string $label): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (!is_numeric($v)) {
            throw new \RuntimeException("Ligne $line : « $label » n'est pas une date ($v).");
        }

        return Date::excelToDateTimeObject((float) $v)->format('Y-m-d');
    }
}
