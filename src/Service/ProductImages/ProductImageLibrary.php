<?php

namespace App\Service\ProductImages;

use App\Service\Tools\SharePointClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Photos et sketchs des articles, lus sur SharePoint (lecture seule, Microsoft Graph).
 *
 * - Photos « ghost » : MARKETING/ASSETS/GHOST/<collection>/… (dossiers de rangement irréguliers d'une collection à l'autre,
 *   casse non respectée) ;
 * - Sketchs (textile et accessoires seulement) : PRODUCTS/APPAREL/COLLECTIONS/<collection>/PNG/….
 *
 * On ne s'appuie jamais sur les noms de sous-dossiers : chaque dossier de collection est parcouru en entier et l'image est
 * retrouvée par le code article à 7 chiffres du nom de fichier (à défaut, du dossier qui la contient, cas de FW27).
 * Règles : la vue _1 passe en premier (puis _2, _3…) ; à vue égale, la collection la plus récente ; les dossiers à valider /
 * KO / archives / PSD / vidéos et les fichiers _NON_OK ou _logtex sont ignorés. Quand un article n'a pas de photo, son
 * sketch est servi à la place, sans mention (c'est voulu).
 *
 * L'index (code → fichier retenu) est reconstruit par app:suivi-sellin:images-index ; les images sont servies depuis un
 * cache disque (var/product_images), remplie à la première demande.
 */
final class ProductImageLibrary
{
    private const BASE = 'https://lecoqsportif.sharepoint.com/Documents%20partages/';
    private const PHOTO_ROOT = 'MARKETING/ASSETS/GHOST';
    private const SKETCH_ROOT = 'PRODUCTS/APPAREL/COLLECTIONS';
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp'];
    /** Dossiers ignorés (nom entier ou contenant ces mots, sans tenir compte de la casse). */
    private const EXCLUDED_FOLDER = '/^(ko\b.*|.*\bko$|.*valider.*|archives?|.*archive.*|psd|video.*|.*vid[eé]o.*)$/iu';
    private const EXCLUDED_FILE = '/(non[_ -]?ok|logtex|copia|copy)/i';

    /** @var array{builtAt:int,photos:array<string,array<string,mixed>>,sketches:array<string,array<string,mixed>>}|null */
    private ?array $index = null;

    public function __construct(
        private readonly SharePointClient $sharePoint,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%/var/suivi_sellin/images_index.json')]
        private readonly string $indexPath,
        #[Autowire('%kernel.project_dir%/var/product_images')]
        private readonly string $cacheDir,
    ) {
    }

    // ------------------------------------------------------------------ index

    /**
     * Parcourt SharePoint et écrit l'index.
     *
     * @return array{photos:int,sketches:int,folders:int}
     */
    public function rebuild(): array
    {
        $stats = ['folders' => 0];
        $photos = [];
        $sketches = [];

        foreach ($this->sharePoint->listFolder(self::BASE . self::PHOTO_ROOT) as $coll) {
            if (!$coll['isFolder']) {
                continue;
            }
            // Collections SS22, FW27… ; le dossier « FAIT » (anciens articles) a le rang le plus bas.
            if (preg_match('/^(SS|FW)(\d{2})$/i', $coll['name'], $m)) {
                $rank = $this->rank($m[1], (int) $m[2]);
            } elseif (strcasecmp($coll['name'], 'FAIT') === 0) {
                $rank = 0;
            } else {
                continue;
            }
            $this->walk(self::BASE . self::PHOTO_ROOT . '/' . rawurlencode($coll['name']), $rank, $photos, $stats, true);
        }

        foreach ($this->sharePoint->listFolder(self::BASE . self::SKETCH_ROOT) as $coll) {
            if (!$coll['isFolder'] || !preg_match('/^(SS|FW)(\d{2})$/i', $coll['name'], $m)) {
                continue;
            }
            $png = self::BASE . self::SKETCH_ROOT . '/' . rawurlencode($coll['name']) . '/PNG';
            try {
                $this->walk($png, $this->rank($m[1], (int) $m[2]), $sketches, $stats, false);
            } catch (\Throwable $e) {
                $this->logger->warning('ProductImageLibrary : dossier PNG illisible', ['collection' => $coll['name'], 'error' => $e->getMessage()]);
            }
        }

        $index = ['builtAt' => time(), 'photos' => $photos, 'sketches' => $sketches];
        $dir = dirname($this->indexPath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Impossible de créer $dir");
        }
        $tmp = $this->indexPath . '.tmp' . getmypid();
        file_put_contents($tmp, json_encode($index, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        rename($tmp, $this->indexPath);
        $this->index = $index;

        return ['photos' => count($photos), 'sketches' => count($sketches), 'folders' => $stats['folders']];
    }

    /**
     * @param array<string,array<string,mixed>> $found code → meilleur fichier trouvé
     * @param array{folders:int} $stats
     */
    private function walk(string $url, int $rank, array &$found, array &$stats, bool $photos, ?string $folderCode = null): void
    {
        ++$stats['folders'];
        foreach ($this->sharePoint->listFolder($url) as $item) {
            if ($item['isFolder']) {
                if (preg_match(self::EXCLUDED_FOLDER, $item['name'])) {
                    continue;
                }
                $code = preg_match('/^\\d{7}$/', $item['name']) ? $item['name'] : $folderCode;
                $this->walk($url . '/' . rawurlencode($item['name']), $rank, $found, $stats, $photos, $code);
                continue;
            }
            $dot = strrpos($item['name'], '.');
            $ext = $dot === false ? '' : strtolower(substr($item['name'], $dot + 1));
            if (!in_array($ext, self::IMAGE_EXT, true) || preg_match(self::EXCLUDED_FILE, $item['name']) || $item['id'] === '') {
                continue;
            }
            $stem = substr($item['name'], 0, $dot);
            $view = 1;
            if (preg_match('/^(\\d{7})(?!\\d)(?:\\s*[_\\-]+\\s*(?:new_?)?\\s*0*(\\d+))?/i', $stem, $m)) {
                $code = $m[1];
                $view = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 1;
            } elseif ($folderCode !== null) {
                $code = $folderCode;
                // Fichier sans code dans le nom, rangé dans un dossier de code (FW27) : vue inconnue, derniers choix.
                $view = 99;
            } else {
                continue;
            }
            if ($view < 1) {
                $view = 1;
            }
            // Meilleur choix : collection la plus récente d'abord, puis vue la plus basse (_1 en premier), puis fichier le plus récent.
            $cand = ['d' => $item['driveId'], 'i' => $item['id'], 'n' => $item['name'], 'r' => $rank, 'v' => $view, 'e' => $ext, 'm' => $item['lastModified']];
            $cur = $found[$code] ?? null;
            if ($cur === null || $this->better($cand, $cur)) {
                $found[$code] = $cand;
            }
        }
    }

    /**
     * Le candidat $a bat-il le choix actuel $b ? Collection la plus récente, puis vue la plus basse (_1 en premier),
     * puis fichier modifié le plus récemment.
     *
     * @param array<string,mixed> $a
     * @param array<string,mixed> $b
     */
    private function better(array $a, array $b): bool
    {
        if ($a['r'] !== $b['r']) {
            return $a['r'] > $b['r'];
        }
        if ($a['v'] !== $b['v']) {
            return $a['v'] < $b['v'];
        }

        return strcmp((string) $a['m'], (string) $b['m']) > 0;
    }

    private function rank(string $season, int $year): int
    {
        return $year * 10 + (strtoupper($season) === 'SS' ? 1 : 2);
    }

    /** @return array{builtAt:int,photos:array<string,array<string,mixed>>,sketches:array<string,array<string,mixed>>} */
    public function index(): array
    {
        if ($this->index === null) {
            $json = is_file($this->indexPath) ? file_get_contents($this->indexPath) : false;
            $this->index = $json !== false ? json_decode($json, true) : null;
            $this->index ??= ['builtAt' => 0, 'photos' => [], 'sketches' => []];
        }

        return $this->index;
    }

    /** @return array<string,true> tous les codes qui ont une photo ou un sketch */
    public function codes(): array
    {
        $index = $this->index();

        return array_fill_keys(array_map('strval', array_merge(array_keys($index['photos']), array_keys($index['sketches']))), true);
    }

    /** @return array{kind:string,entry:array<string,mixed>}|null la photo, à défaut le sketch */
    public function resolve(string $code): ?array
    {
        $index = $this->index();
        if (isset($index['photos'][$code])) {
            return ['kind' => 'photo', 'entry' => $index['photos'][$code]];
        }
        if (isset($index['sketches'][$code])) {
            return ['kind' => 'sketch', 'entry' => $index['sketches'][$code]];
        }

        return null;
    }

    // ------------------------------------------------------------------ fichiers servis

    /**
     * Chemin du fichier image à servir (cache disque, rempli à la première demande), ou null si l'article n'a ni photo
     * ni sketch, ou si SharePoint ne répond pas.
     *
     * @return array{path:string,mime:string}|null
     */
    public function file(string $code): ?array
    {
        $hit = $this->resolve($code);
        if ($hit === null) {
            return null;
        }
        $e = $hit['entry'];
        $base = $this->cacheDir . '/' . $code . '_' . substr(md5($e['i']), 0, 8);
        $path = glob($base . '.*')[0] ?? null;
        if ($path === null || str_contains($path, '.tmp')) {
            try {
                $path = $this->fetch($hit['kind'], $e, $base);
            } catch (\Throwable $ex) {
                $this->logger->warning('ProductImageLibrary : image indisponible', ['code' => $code, 'error' => $ex->getMessage()]);

                return null;
            }
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return ['path' => $path, 'mime' => match ($ext) {'png' => 'image/png', 'webp' => 'image/webp', default => 'image/jpeg'}];
    }

    /**
     * @param array<string,mixed> $e
     * @return string chemin du fichier mis en cache
     */
    private function fetch(string $kind, array $e, string $base): string
    {
        if (!is_dir($this->cacheDir) && !mkdir($this->cacheDir, 0775, true) && !is_dir($this->cacheDir)) {
            throw new \RuntimeException("Impossible de créer {$this->cacheDir}");
        }
        // Photos : miniature 800 px générée par SharePoint (les originaux font 1 à 2 Mo) ; à défaut, l'original.
        // Sketchs : l'original (20 à 110 Ko), le PNG reste transparent.
        $thumb = $kind === 'photo' ? $this->sharePoint->thumbnail($e['d'], $e['i'], 'large') : null;
        $tmp = $base . '.tmp' . getmypid();
        if ($thumb !== null) {
            file_put_contents($tmp, $thumb);
            $path = $base . '.jpg';
        } else {
            $this->sharePoint->downloadItem($e['d'], $e['i'], $tmp);
            $path = $base . '.' . ($e['e'] === 'jpeg' ? 'jpg' : $e['e']);
        }
        rename($tmp, $path);

        return $path;
    }

    /** Supprime du cache les images qui ne correspondent plus à l'index (fichier remplacé ou retiré). */
    public function cleanCache(): int
    {
        if (!is_dir($this->cacheDir)) {
            return 0;
        }
        $keep = [];
        $index = $this->index();
        foreach (['photos', 'sketches'] as $k) {
            foreach ($index[$k] as $code => $e) {
                $keep[$code . '_' . substr(md5($e['i']), 0, 8)] = true;
            }
        }
        $n = 0;
        foreach (glob($this->cacheDir . '/*') ?: [] as $f) {
            if (!isset($keep[pathinfo($f, PATHINFO_FILENAME)])) {
                @unlink($f);
                ++$n;
            }
        }

        return $n;
    }
}
