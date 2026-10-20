<?php

namespace App\Service\SuiviSellin;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Jeux de données prêts à servir du module Suivi Sellin global, un fichier JSON par jeu dans var/suivi_sellin/.
 *
 * Construire un jeu prend ~30 s (le carnet fait ~490 000 lignes à lire dans le cube) : on ne le fait pas à chaque
 * affichage. La commande app:suivi-sellin:refresh (cron) les prépare ; à défaut, le premier affichage du jour les
 * construit. Dossier var/suivi_sellin/ et non le cache Symfony : un cache:clear (à chaque déploiement) ne doit pas
 * obliger à tout reconstruire. Écriture atomique (fichier temporaire puis rename) et verrou pour qu'un seul
 * processus construise un jeu à la fois.
 */
final class SuiviSellinBlobStore
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/var/suivi_sellin')]
        private readonly string $dir,
    ) {
    }

    /**
     * @param callable():string $builder construit le JSON du jeu
     * @return array{json:string, builtAt:int}
     */
    public function get(string $name, callable $builder, int $ttlSeconds): array
    {
        $cached = $this->read($name);
        if ($cached !== null && time() - $cached['builtAt'] < $ttlSeconds) {
            return $cached;
        }

        return $this->build($name, $builder);
    }

    /**
     * @param callable():string $builder
     * @return array{json:string, builtAt:int}
     */
    public function build(string $name, callable $builder): array
    {
        if (!is_dir($this->dir) && !mkdir($this->dir, 0775, true) && !is_dir($this->dir)) {
            throw new \RuntimeException("Impossible de créer {$this->dir}");
        }

        // Une construction à la fois par jeu ; les autres attendent puis relisent le résultat.
        $lock = fopen($this->path($name) . '.lock', 'c');
        flock($lock, LOCK_EX);
        try {
            $current = $this->read($name);
            if ($current !== null && time() - $current['builtAt'] < 60) {
                return $current; // un autre processus vient de le construire
            }

            @ini_set('memory_limit', '1G');
            @set_time_limit(0);
            $json = $builder();
            $tmp = $this->path($name) . '.tmp' . getmypid();
            file_put_contents($tmp, $json);
            rename($tmp, $this->path($name));

            return ['json' => $json, 'builtAt' => time()];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** @return array{json:string, builtAt:int}|null */
    public function read(string $name): ?array
    {
        $path = $this->path($name);
        if (!is_file($path)) {
            return null;
        }
        $json = file_get_contents($path);

        return $json === false ? null : ['json' => $json, 'builtAt' => (int) filemtime($path)];
    }

    private function path(string $name): string
    {
        return $this->dir . '/' . preg_replace('/[^a-z0-9_]/i', '', $name) . '.json';
    }
}
