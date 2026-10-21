<?php

namespace App\Service\SuiviSellin;

use App\Service\Tools\SharePointClient;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Listes d'articles du sell-out par saison et par enseigne (périmètre du reporting « Push Réassort »).
 *
 * - Saisons passées (SS25, FW25, SS26) : graine `articles_seed.json`, extraite du jeu de données du template
 *   (les fichiers Push 2025 ne sont pas disponibles en entier dans l'archive SharePoint).
 * - Saisons courantes : onglet « Item Detail » des fichiers de la dernière semaine déposée sur SharePoint.
 *   La saison, l'enseigne et la famille viennent de l'onglet « Parameter » de chaque fichier (Serie, Source code,
 *   Class Code) : un fichier ne dépend donc pas de son nom.
 *
 * Lecture seule : rien n'est écrit sur SharePoint.
 */
class SelloutArticleLists
{
    public const ROOT_URL = 'https://lecoqsportif.sharepoint.com/Documents%20partages/SALES/Reporting%20-%20Push%20R%C3%A9assort%20%28SellOut%29';

    /** Class Code du fichier → famille du template. */
    private const FAMILIES = ['APL' => 'Textile', 'FTW' => 'Footwear', 'HDW' => 'Accessoires'];

    public function __construct(private readonly SharePointClient $sharePoint)
    {
    }

    /**
     * @return array<string, array<string, list<array{code:string,desc:string,line:string,fam:string}>>> saison → enseigne → articles
     */
    public function seed(): array
    {
        $json = json_decode((string) file_get_contents(__DIR__ . '/../../Infrastructure/SelloutReference/articles_seed.json'), true, 512, JSON_THROW_ON_ERROR);

        return $json['seasons'];
    }

    /**
     * Dossier de la dernière semaine (format AAAA_WNN) : nom et liste des fichiers .xlsx.
     *
     * @return array{folder:string,files:list<string>}
     */
    public function latestWeek(): array
    {
        $best = null;
        foreach ($this->sharePoint->listFolder(self::ROOT_URL) as $item) {
            if ($item['isFolder'] && preg_match('/^(\d{4})_W(\d{2})$/', $item['name'], $m)) {
                $k = (int) ($m[1] . $m[2]);
                if ($best === null || $k > $best[0]) {
                    $best = [$k, $item['name']];
                }
            }
        }
        if ($best === null) {
            throw new \RuntimeException('Aucun dossier de semaine trouvé dans le reporting Push Réassort.');
        }

        $files = [];
        foreach ($this->sharePoint->listFolder(self::ROOT_URL . '/' . $best[1]) as $item) {
            if (!$item['isFolder'] && str_ends_with(strtolower($item['name']), '.xlsx') && !str_starts_with($item['name'], '~$')) {
                $files[] = $item['name'];
            }
        }
        sort($files);

        return ['folder' => $best[1], 'files' => $files];
    }

    /**
     * Lecture des fichiers de la dernière semaine : articles par saison et enseigne, quantités RAL / préparation par
     * magasin et article, informations des magasins.
     *
     * @return array{
     *   week:string,
     *   articles:array<string, array<string, list<array{code:string,desc:string,line:string,fam:string}>>>,
     *   stock:array<string, array<string, array<string,array{ral:float,prep:float,sold:float,stock:float,sv:float,min:int}>>>,
     *   stores:array<string, list<string>>
     * } `stock` : saison → enseigne → « magasin|article » → valeurs des fichiers (ventes, stock, préparation, RAL, 1re semaine)
     */
    public function fromLatestWeek(): array
    {
        $week = $this->latestWeek();
        $articles = [];
        $stock = [];
        $stores = [];
        foreach ($week['files'] as $name) {
            $tmp = tempnam(sys_get_temp_dir(), 'sellout_');
            try {
                $this->sharePoint->download(self::ROOT_URL . '/' . $week['folder'] . '/' . rawurlencode($name), $tmp);
                $r = $this->readFile($tmp);
            } finally {
                @unlink($tmp);
            }
            foreach ($r['items'] as $code => $it) {
                $articles[$r['season']][$r['source']][$code] = $it + ['fam' => $r['family']];
            }
            foreach ($r['stock'] as $k => $v) {
                $stock[$r['season']][$r['source']][$k] = $v;
            }
            $stores += $r['stores'];
        }
        foreach ($articles as $season => $bySource) {
            foreach ($bySource as $source => $items) {
                ksort($items, SORT_STRING);
                $articles[$season][$source] = array_values($items);
            }
        }
        ksort($articles);

        return ['week' => $week['folder'], 'articles' => $articles, 'stock' => $stock, 'stores' => $stores];
    }

    /**
     * @return array{season:string,source:string,family:string,items:array<string,array{code:string,desc:string,line:string}>,stock:array<string,array{ral:float,prep:float,sold:float,stock:float,sv:float,min:int}>,stores:array<string,list<string>>}
     */
    private function readFile(string $path): array
    {
        $filter = new class implements IReadFilter {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return match ($worksheetName) {
                    'Parameter' => $row <= 10 && $columnAddress === 'B',
                    'Item Detail' => in_array($columnAddress, ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'L', 'N', 'O', 'P'], true),
                    'Variant Detail' => in_array($columnAddress, ['C', 'F', 'U', 'V'], true),
                    default => false,
                };
            }
        };
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(['Parameter', 'Item Detail', 'Variant Detail']);
        $reader->setReadFilter($filter);
        $book = $reader->load($path);

        $p = $book->getSheetByName('Parameter');
        $source = trim((string) $p?->getCell('B3')->getValue());
        $class = trim((string) $p?->getCell('B4')->getValue());
        $season = trim((string) $p?->getCell('B8')->getValue());
        if ($source === '' || $season === '' || !isset(self::FAMILIES[$class])) {
            throw new \RuntimeException("Onglet Parameter inattendu (source « $source », classe « $class », série « $season »).");
        }

        $ws = $book->getSheetByName('Item Detail');
        $items = [];
        $stock = [];
        $stores = [];
        for ($i = 2, $h = $ws->getHighestRow(); $i <= $h; ++$i) {
            $code = trim((string) $ws->getCell("F$i")->getValue());
            $cust = trim((string) $ws->getCell("C$i")->getValue());
            if ($code === '') {
                continue;
            }
            $items[$code] ??= [
                'code' => $code,
                'desc' => trim((string) $ws->getCell("G$i")->getValue()),
                'line' => trim((string) $ws->getCell("H$i")->getValue()),
            ];
            if ($cust !== '') {
                $stock[$cust . '|' . $code] = [
                    'ral' => 0.0,
                    'prep' => (float) $ws->getCell("P$i")->getValue(),
                    'sold' => (float) $ws->getCell("L$i")->getValue(),
                    'stock' => (float) $ws->getCell("N$i")->getValue(),
                    'sv' => (float) $ws->getCell("O$i")->getValue(),
                    'min' => (int) $ws->getCell("J$i")->getValue(),
                ];
                $stores[$cust] ??= [
                    trim((string) $ws->getCell("A$i")->getValue()),
                    trim((string) $ws->getCell("B$i")->getValue()),
                    trim((string) $ws->getCell("D$i")->getValue()),
                    trim((string) $ws->getCell("E$i")->getValue()),
                ];
            }
        }

        // RAL : somme des tailles (onglet Variant Detail, valeurs déposées par le complément Excel).
        $vs = $book->getSheetByName('Variant Detail');
        for ($i = 2, $h = $vs->getHighestRow(); $i <= $h; ++$i) {
            $cust = trim((string) $vs->getCell("C$i")->getValue());
            $code = trim((string) $vs->getCell("F$i")->getValue());
            if ($cust === '' || $code === '') {
                continue;
            }
            $k = $cust . '|' . $code;
            if (isset($stock[$k])) {
                $stock[$k]['ral'] += (float) $vs->getCell("V$i")->getValue();
            }
        }
        $book->disconnectWorksheets();

        return ['season' => $season, 'source' => $source, 'family' => self::FAMILIES[$class], 'items' => $items, 'stock' => $stock, 'stores' => $stores];
    }
}
