<?php

namespace App\Service\SuiviSellin;

/**
 * Point d'entrée des jeux de données du module : nom du jeu (celui de la balise <script id="…"> attendue par le
 * JavaScript du template) => constructeur. Un jeu s'ajoute ici quand l'onglet qui l'utilise est livré.
 */
final class SuiviSellinData
{
    /** Un jeu plus vieux que cela est reconstruit à l'affichage (le cron le garde normalement frais). */
    private const TTL = 6 * 3600;

    public function __construct(
        private readonly SuiviSellinBlobStore $store,
        private readonly MainDataBuilder $main,
        private readonly BacklogBuilder $backlog,
        private readonly RemisesBuilder $remises,
        private readonly FacturationBuilder $facturation,
        private readonly AccompagnementBuilder $accompagnement,
        private readonly SelloutBuilder $sellout,
    ) {
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->builders());
    }

    /** @return array{json:string, builtAt:int} */
    public function get(string $name): array
    {
        $builders = $this->builders();
        if (!isset($builders[$name])) {
            throw new \InvalidArgumentException("Jeu de données inconnu : {$name}");
        }

        // Le sell-out lit des fichiers SharePoint et des millions de lignes du cube (plusieurs minutes) : jamais construit
        // à l'affichage. Le jeu du dernier rafraîchissement est servi tel quel, quel que soit son âge ; à défaut, un jeu
        // vide (l'onglet Sell-out n'a alors rien à afficher, les autres onglets ne sont pas touchés).
        if ($name === 'sodata') {
            return $this->store->read($name) ?? ['json' => '{}', 'builtAt' => time()];
        }

        return $this->store->get($name, $builders[$name], self::TTL);
    }

    /** Reconstruit un jeu quel que soit son âge (commande de rafraîchissement). */
    public function refresh(string $name): array
    {
        $builders = $this->builders();
        if (!isset($builders[$name])) {
            throw new \InvalidArgumentException("Jeu de données inconnu : {$name}");
        }

        return $this->store->build($name, $builders[$name]);
    }

    /** @return array<string, callable():string> */
    private function builders(): array
    {
        return [
            'data'    => fn (): string => $this->encode($this->main->build()),
            // Le backlog reprend la hiérarchie des clients du jeu principal : il est construit après lui.
            'backlog' => fn (): string => $this->encode($this->backlog->build($this->mainDecoded())),
            'remises' => fn (): string => $this->encode($this->remises->build($this->mainDecoded())),
            'factdata' => fn (): string => $this->encode($this->facturation->buildFact($this->mainDecoded())),
            'readata' => fn (): string => $this->encode($this->facturation->buildRea($this->mainDecoded())),
            'accdata' => fn (): string => $this->encode($this->accompagnement->buildAcc($this->mainDecoded())),
            'anndata' => fn (): string => $this->encode($this->accompagnement->buildAnn($this->mainDecoded())),
            'robdata' => fn (): string => $this->encode($this->facturation->buildRob($this->mainDecoded())),
            'sodata'  => fn (): string => $this->encode($this->sellout->build()),
        ];
    }

    /** @return array<string,mixed> */
    private function mainDecoded(): array
    {
        return json_decode($this->get('data')['json'], true, 512, JSON_THROW_ON_ERROR);
    }

    /** @param array<string,mixed> $data */
    private function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
