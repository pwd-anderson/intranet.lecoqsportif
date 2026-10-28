<?php

namespace App\Service\Export;

use App\Repository\AggridOptionRepository;
use App\Service\AgGrid\Ssrm\SsrmRequest;
use App\Service\Achat;
use App\Service\BacklogClientV2;
use App\Service\Divers;
use App\Service\Sales;
use App\Service\Stock;
use App\Service\Tools\Helpers;

/**
 * Génère, pour un export automatique, le même contenu que le bouton d'export manuel de chaque stat
 * (mêmes colonnes visibles, mêmes en-têtes, mêmes valeurs ; seul le style Excel est allégé).
 *
 * Deux conventions d'export manuel sont reproduites :
 *  - grilles « chargées en entier » (Achats, Stock) : export AG Grid — nombres bruts, dates au format jj/mm/aaaa,
 *    texte tel quel ;
 *  - Etat des commandes clients (SSRM) : export ExcelJS — valeurs brutes de la requête.
 *
 * Les services appelés avalent leurs erreurs SQL (ils notifient et renvoient []) : un résultat vide est donc
 * traité comme un échec par la commande, jamais déposé.
 */
class AskSupplyDataExporter
{
    public const BACKLOG_FOURNISSEUR = 'backlog-fournisseur';
    public const BACKLOG_CLIENTS = 'backlog-clients';
    public const STOCK_ALLOCATION = 'stock-allocation';
    public const PRODUITS = 'produits';
    public const RECEPTION_FOURNISSEUR = 'reception-fournisseur';
    public const ETAT_COMMANDES_CLIENTS = 'etat-commandes-clients';
    public const BASE_ARTICLES_TARIFS = 'base-articles-tarifs';

    /** Nom du fichier déposé pour chaque export (fixe : le fichier de la veille est remplacé). */
    public const FILES = [
        self::BACKLOG_FOURNISSEUR => 'Backlog_Fournisseur.xlsx',
        self::BACKLOG_CLIENTS => 'Backlog_Clients.xlsx',
        self::STOCK_ALLOCATION => 'Stock_Allocation.xlsx',
        self::PRODUITS => 'Produits.xlsx',
        self::RECEPTION_FOURNISSEUR => 'Reception_Fournisseur.xlsx',
        self::ETAT_COMMANDES_CLIENTS => 'Etat_Commandes_Clients.xlsx',
        self::BASE_ARTICLES_TARIFS => 'Base_Articles_Tarifs.xlsx',
    ];

    private const ETAT_COMMANDES_CHUNK = 40000;

    public function __construct(
        private readonly AggridOptionRepository $aggridOptions,
        private readonly Achat $achat,
        private readonly Stock $stock,
        private readonly Sales $sales,
        private readonly BacklogClientV2 $backlogClient,
        private readonly Divers $divers,
        private readonly Helpers $helpers,
    ) {
    }

    /**
     * @param list<string> $produitsCollections collections de l'export Produits
     * @param int          $etatCommandesCollections nombre de collections les plus récentes de l'export Etat des commandes clients
     * @return int nombre de lignes écrites (hors en-tête)
     */
    public function export(string $key, string $targetPath, array $produitsCollections, int $etatCommandesCollections): int
    {
        return match ($key) {
            self::BACKLOG_FOURNISSEUR => $this->writeGrid(
                'backlog_fournisseur_x3_grid',
                $targetPath,
                array_merge($this->achat->getBacklogFournisseur(), $this->achat->getBacklogFournisseurIntersites())
            ),
            self::BACKLOG_CLIENTS => $this->writeBacklogClients($targetPath),
            // « Global » = aucun filtre de groupe de sites.
            self::STOCK_ALLOCATION => $this->writeGrid('stock_allocation_grid', $targetPath, $this->stock->getStockAllocation(null)),
            self::PRODUITS => $this->writeGrid(
                'stock_produits_grid',
                $targetPath,
                $this->stock->getStockProduits($produitsCollections, null, null),
                self::produitsCell(...)
            ),
            self::RECEPTION_FOURNISSEUR => $this->writeGrid('reception_fournisseur_grid', $targetPath, $this->achat->getReceptionFournisseur()),
            self::ETAT_COMMANDES_CLIENTS => $this->writeEtatCommandesClients($targetPath, $etatCommandesCollections),
            self::BASE_ARTICLES_TARIFS => $this->writeGrid('base_articles_tarifs_grid', $targetPath, $this->achat->getBaseArticlesTarifs()),
            default => throw new \InvalidArgumentException("Export inconnu : $key"),
        };
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::FILES);
    }

    /** Collections de l'export Etat des commandes clients : les N plus récentes (liste X3, ordre décroissant). */
    public function latestCollections(int $count): array
    {
        return array_slice($this->divers->getCollections(), 0, $count);
    }

    /**
     * @param iterable<mixed> $rows
     * @param (callable(string,mixed):mixed)|null $cellRule règle propre à l'export manuel de la stat (processCellCallback)
     */
    private function writeGrid(string $gridName, string $targetPath, iterable $rows, ?callable $cellRule = null): int
    {
        $columns = $this->visibleColumns($gridName);
        $writer = new XlsxStreamWriter($targetPath, array_column($columns, 'header'));

        foreach ($rows as $row) {
            $row = (array) $this->helpers->convertArrayToUtf8($row);
            $line = [];
            foreach ($columns as $col) {
                $value = $row[$col['field']] ?? null;
                if ($cellRule !== null) {
                    $value = $cellRule($col['field'], $value);
                }
                $line[] = $this->gridValue($col, $value);
            }
            $writer->addRow($line);
        }

        $count = $writer->rowCount();
        $writer->close();

        return $count;
    }

    private function writeEtatCommandesClients(string $targetPath, int $collectionCount): int
    {
        $collections = $this->latestCollections($collectionCount);
        if ($collections === []) {
            throw new \RuntimeException('Aucune collection X3 disponible.');
        }

        $columns = $this->visibleColumns('etat_commandes_clients_x3_grid');
        $writer = new XlsxStreamWriter($targetPath, array_column($columns, 'header'));

        $offset = 0;
        while (true) {
            $response = $this->sales->getEtatCommandesClientsX3Paginated(SsrmRequest::fromArray([
                'startRow' => $offset,
                'endRow' => $offset + self::ETAT_COMMANDES_CHUNK,
                'filterModel' => [],
                'sortModel' => [],
                'options' => ['collections' => $collections, 'includeStock' => true, 'isExport' => true],
            ]));

            foreach ($response->rows as $row) {
                $row = (array) $this->helpers->convertArrayToUtf8($row);
                $line = [];
                foreach ($columns as $col) {
                    // Export SSRM manuel : valeurs brutes de la requête, sans formatage.
                    $line[] = $this->numeric($col, $row[$col['field']] ?? null);
                }
                $writer->addRow($line);
            }

            if (count($response->rows) < self::ETAT_COMMANDES_CHUNK) {
                break;
            }
            $offset += self::ETAT_COMMANDES_CHUNK;
        }

        $count = $writer->rowCount();
        $writer->close();

        return $count;
    }

    /** Contenu du « CSV complet » du Backlog Clients (colonnes visibles, ordre, en-têtes) au format xlsx : nombres bruts, texte tel quel. */
    private function writeBacklogClients(string $targetPath): int
    {
        $columns = $this->visibleColumns('backlog_client_v2_grid');
        $writer = new XlsxStreamWriter($targetPath, array_column($columns, 'header'));

        foreach ($this->backlogClient->iterateExportRows($this->backlogClientsRequest()) as $row) {
            $line = [];
            foreach ($columns as $col) {
                $line[] = $this->numeric($col, $row[$col['field']] ?? null);
            }
            $writer->addRow($line);
        }

        $count = $writer->rowCount();
        $writer->close();

        return $count;
    }

    /** Backlog Clients complet : toutes collections, avec le stock. */
    private function backlogClientsRequest(): SsrmRequest
    {
        return SsrmRequest::fromArray([
            'filterModel' => [],
            'sortModel' => [],
            'options' => ['allCollections' => true, 'includeStock' => true],
        ]);
    }

    /** @return list<array{field:string,header:string,type:string,date:bool}> */
    private function visibleColumns(string $gridName): array
    {
        $columns = [];
        foreach ($this->aggridOptions->findBy(['gridName' => $gridName], ['orderIndex' => 'ASC']) as $option) {
            if ($option->isVisible() === false) {
                continue;
            }
            $columns[] = [
                'field' => (string) $option->getField(),
                'header' => (string) ($option->getHeaderName() ?: $option->getField()),
                'type' => (string) $option->getType(),
                // Le constructeur de colonnes AG Grid applique dateFormatter à tout filtre de date.
                'date' => $option->getFilter() === 'agDateColumnFilter' || $option->getValueFormatter() === 'dateFormatter',
            ];
        }
        if ($columns === []) {
            throw new \RuntimeException("Configuration AG Grid introuvable ou vide : $gridName");
        }

        return $columns;
    }

    /** Valeur d'une cellule telle que l'export AG Grid la produit. */
    private function gridValue(array $col, mixed $value): int|float|string|null
    {
        if ($col['date'] && is_string($value) && $value !== '') {
            // dateFormatter : "aaaa-mm-jj" -> "jj/mm/aaaa"
            $parts = explode('-', $value);

            return count($parts) >= 3 ? $parts[2] . '/' . $parts[1] . '/' . $parts[0] : $value;
        }

        return $this->numeric($col, $value);
    }

    private function numeric(array $col, mixed $value): int|float|string|null
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (in_array($col['type'], ['integer', 'decimal', 'percent'], true) && is_numeric($value)) {
            return $value + 0;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        return (string) $value;
    }

    /** processCellCallback de l'export manuel « Produits » : photo vide, taux de dépréciation en pourcentage. */
    private static function produitsCell(string $field, mixed $value): mixed
    {
        if ($field === 'PHOTO') {
            return '';
        }
        if ($field === 'TAUX_DEPRECIATION') {
            return ($value === null || $value === '') ? '' : number_format((float) $value * 100, 0, '.', '') . ' %';
        }

        return $value;
    }
}
