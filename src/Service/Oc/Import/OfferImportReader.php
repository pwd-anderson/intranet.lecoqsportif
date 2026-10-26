<?php

namespace App\Service\Oc\Import;

use App\Service\Oc\Input\OfferInput;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

/**
 * Lecture du fichier d'import des lignes d'une OC (Excel .xlsx / .xls ou .csv). Un fichier = un client = une OC : le client et les
 * dates sont ceux de l'écran, le fichier ne porte que les lignes.
 *
 * Colonnes reconnues (en-têtes insensibles à la casse, aux accents et à la ponctuation ; l'ordre est libre) :
 *   - Article (obligatoire) : code d'un article parent (« 2210037 ») ou d'une taille (« 2210037_T9 ») ;
 *   - Tarif (obligatoire) : tarif OC net ;
 *   - Qté min / Qté max, Commentaire (facultatives).
 * L'en-tête peut se trouver dans les {@see self::HEADER_SEARCH_ROWS} premières lignes. Les lignes vides sont ignorées.
 *
 * Ne lit que des valeurs (jamais de calcul de formule, pas de macro) ; première feuille seulement. Chaque ligne renvoyée porte ses
 * erreurs de FORME (tarif illisible, quantités…) ; l'existence des articles dans X3 est contrôlée plus loin (OfferImportChecker).
 */
final class OfferImportReader
{
    public const MAX_BYTES = 2_000_000;
    public const EXTENSIONS = ['xlsx', 'xls', 'csv'];
    private const HEADER_SEARCH_ROWS = 6;
    private const MAX_TARIFF = 9999999;

    /** Noms d'en-tête reconnus par champ (déjà normalisés : minuscules, sans accent ni ponctuation). */
    private const HEADERS = [
        'article' => ['article', 'codearticle', 'refarticle', 'reference', 'ref', 'itmref', 'sku', 'code', 'articlecode'],
        'tariff' => ['tarif', 'tarifoc', 'tarifocnet', 'tarifnet', 'prix', 'prixnet', 'pxnet', 'net', 'prixoc'],
        'minQty' => ['qtemin', 'quantitemin', 'minqty', 'qtemini', 'min', 'quantiteminimum'],
        'maxQty' => ['qtemax', 'quantitemax', 'maxqty', 'qtemaxi', 'max', 'quantitemaximum'],
        'comment' => ['commentaire', 'comment', 'remarque', 'observation', 'observations', 'commentaires'],
    ];

    /**
     * @return list<array{line:int,article:string,tariff:?float,minQty:?int,maxQty:?int,comment:?string,errors:list<string>}>
     *
     * @throws OfferImportException fichier inutilisable (le message est destiné à l'utilisateur)
     */
    public function read(string $path, string $extension): array
    {
        $extension = strtolower($extension);
        if (!in_array($extension, self::EXTENSIONS, true)) {
            throw new OfferImportException('Format non pris en charge : utilisez un fichier .xlsx, .xls ou .csv.');
        }
        if (!is_file($path) || filesize($path) === 0) {
            throw new OfferImportException('Le fichier est vide.');
        }
        if (filesize($path) > self::MAX_BYTES) {
            throw new OfferImportException('Le fichier est trop volumineux (2 Mo maximum).');
        }

        // Un fichier envoyé par navigateur est rangé par PHP sous un nom sans extension (/tmp/phpAbC123) : PhpSpreadsheet choisit son
        // lecteur et accepte (ou non) le fichier d'après l'extension. On en lit donc une copie qui porte l'extension d'origine.
        $work = $path;
        $copy = null;
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== $extension) {
            $copy = tempnam(sys_get_temp_dir(), 'ocimp');
            $work = $copy . '.' . $extension;
            if (!@copy($path, $work)) {
                @unlink($copy);
                throw new OfferImportException('Le fichier ne peut pas être lu sur le serveur, réessayez.');
            }
        }

        try {
            $matrix = $this->matrix($work, $extension);
        } catch (\Throwable) {
            throw new OfferImportException('Le fichier est illisible : vérifiez qu\'il s\'agit bien d\'un classeur Excel ou d\'un CSV.');
        } finally {
            if ($copy !== null) {
                @unlink($work);
                @unlink($copy);
            }
        }

        [$headerRow, $columns] = $this->findHeader($matrix);
        if ($headerRow === null) {
            throw new OfferImportException('En-têtes introuvables : la première ligne doit contenir au moins les colonnes « Article » et « Tarif ».');
        }

        $rows = [];
        $seen = [];
        for ($i = $headerRow + 1, $n = count($matrix); $i < $n; ++$i) {
            $cells = $matrix[$i];
            $raw = [];
            foreach ($columns as $field => $col) {
                $raw[$field] = $cells[$col] ?? null;
            }
            if ($this->isBlank($raw)) {
                continue;
            }
            if (count($rows) >= OfferInput::MAX_LINES) {
                throw new OfferImportException('Le fichier dépasse ' . OfferInput::MAX_LINES . ' lignes : une OC ne peut pas en contenir davantage.');
            }

            $errors = [];
            $article = $this->article($raw['article']);
            if ($article === '') {
                $errors[] = 'Article manquant.';
            } elseif (mb_strlen($article) > 50) {
                $errors[] = 'Code article trop long.';
            } elseif (isset($seen[$article])) {
                $errors[] = sprintf('Article déjà présent à la ligne %d du fichier.', $seen[$article]);
            } else {
                $seen[$article] = $i + 1;
            }

            $tariff = $this->number($raw['tariff']);
            if ($tariff === null || $tariff <= 0 || $tariff > self::MAX_TARIFF) {
                $errors[] = 'Tarif manquant ou invalide (nombre positif attendu).';
                $tariff = null;
            }

            $min = $this->quantity($raw['minQty'] ?? null, 0, $errors, 'Qté min');
            $max = $this->quantity($raw['maxQty'] ?? null, OfferInput::MAX_QTY, $errors, 'Qté max');
            if ($min !== null && $max !== null && $min > $max) {
                $errors[] = 'La quantité min dépasse la quantité max.';
            }

            $comment = isset($raw['comment']) ? trim((string) $raw['comment']) : '';
            if (mb_strlen($comment) > OfferInput::MAX_COMMENT) {
                $errors[] = 'Commentaire trop long (' . OfferInput::MAX_COMMENT . ' caractères maximum).';
            }

            $rows[] = [
                'line' => $i + 1,
                'article' => $article,
                'tariff' => $tariff,
                'minQty' => $min,
                'maxQty' => $max,
                'comment' => $comment === '' ? null : $comment,
                'errors' => $errors,
            ];
        }

        if ($rows === []) {
            throw new OfferImportException('Le fichier ne contient aucune ligne à importer sous les en-têtes.');
        }

        return $rows;
    }

    /** @return list<list<mixed>> */
    private function matrix(string $path, string $extension): array
    {
        if ($extension === 'csv') {
            $reader = new Csv();
            $reader->setInputEncoding(Csv::GUESS_ENCODING);
        } else {
            $reader = IOFactory::createReader(ucfirst($extension));
        }
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getSheet(0);

        // Valeurs brutes (aucune mise en forme) : un code article numérique reste un nombre que l'on remet en texte nous-mêmes.
        return $sheet->toArray(null, true, false, false);
    }

    /** @return array{0:?int,1:array<string,int>} ligne d'en-tête et colonne de chaque champ */
    private function findHeader(array $matrix): array
    {
        for ($i = 0, $n = min(self::HEADER_SEARCH_ROWS, count($matrix)); $i < $n; ++$i) {
            $columns = [];
            foreach ($matrix[$i] as $col => $value) {
                $key = $this->normalize((string) $value);
                foreach (self::HEADERS as $field => $names) {
                    if (!isset($columns[$field]) && in_array($key, $names, true)) {
                        $columns[$field] = (int) $col;
                    }
                }
            }
            if (isset($columns['article'], $columns['tariff'])) {
                return [$i, $columns];
            }
        }

        return [null, []];
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = strtr($s, ['à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'œ' => 'oe']);

        return preg_replace('/[^a-z0-9]/', '', $s) ?? '';
    }

    /** @param array<string,mixed> $raw */
    private function isBlank(array $raw): bool
    {
        foreach ($raw as $v) {
            if ($v !== null && trim((string) $v) !== '') {
                return false;
            }
        }

        return true;
    }

    private function article(mixed $v): string
    {
        if (is_float($v) && floor($v) === $v) {
            $v = (int) $v;   // code article saisi comme un nombre dans Excel : 2210037.0 → « 2210037 »
        }

        return preg_replace('/\s+/u', '', trim((string) $v)) ?? '';
    }

    private function number(mixed $v): ?float
    {
        if (is_int($v) || is_float($v)) {
            return (float) $v;
        }
        $s = trim((string) $v);
        if ($s === '') {
            return null;
        }
        $s = preg_replace('/[\s\x{00A0}]|€|eur(os)?/iu', '', $s) ?? '';
        if (str_contains($s, ',') && str_contains($s, '.')) {
            // « 1.234,56 » ou « 1,234.56 » : le dernier séparateur est la virgule décimale.
            $decimal = strrpos($s, ',') > strrpos($s, '.') ? ',' : '.';
            $thousand = $decimal === ',' ? '.' : ',';
            $s = str_replace([$thousand, $decimal], ['', '.'], $s);
        } else {
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : null;
    }

    /** @param list<string> $errors */
    private function quantity(mixed $v, int $default, array &$errors, string $label): ?int
    {
        if ($v === null || trim((string) $v) === '') {
            return $default;
        }
        $n = $this->number($v);
        if ($n === null || $n < 0 || floor($n) !== $n || $n > OfferInput::MAX_QTY * 1000) {
            $errors[] = $label . ' invalide (entier positif attendu).';

            return null;
        }

        return (int) $n;
    }
}
