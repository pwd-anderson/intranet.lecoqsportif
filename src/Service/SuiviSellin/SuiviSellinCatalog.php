<?php

namespace App\Service\SuiviSellin;

use App\Infrastructure\Sql\SqlFileLoader;

/**
 * Catalogue du module Suivi Sellin global : les menus (onglets) du template
 * public/template/suivi_sellen_global_4.html et, pour chacun, les requêtes SQL qui l'alimentent.
 *
 * L'application affiche, tout en bas de chaque onglet, un petit bouton « Requêtes » qui déplie ces requêtes (titre,
 * source, SQL exact). Les requêtes vivent dans
 * src/Infrastructure/Sql/Sei/modules/suivi_sellin/ ; les deux premières (backlog, annulations) sont celles,
 * déjà en production, des stats Backlog Clients et État des commandes clients (elles contiennent des
 * marqueurs {{...}} remplacés par leur service).
 */
final class SuiviSellinCatalog
{
    public function __construct(private readonly SqlFileLoader $sqlFileLoader)
    {
    }

    /**
     * @return array<string, array{title:string, source:string, file:string, note:string}>
     */
    public function queries(): array
    {
        return [
            'carnet' => [
                'title'  => 'Carnet de commandes (sell-in)',
                'source' => 'Vue SEI LCS_X3SALESX (BOH_*)',
                'file'   => 'Sei/modules/suivi_sellin/01_carnet_commandes.sql',
                'note'   => 'Une ligne par saison, type de commande, client et article, 2024-01-SS à 2027-02-FW (environ 490 000 lignes).',
            ],
            'clients' => [
                'title'  => 'Référentiel clients (hiérarchie commerciale)',
                'source' => 'Vue SEI LCS_CUSTOMER',
                'file'   => 'Sei/modules/suivi_sellin/02_referentiel_clients.sql',
                'note'   => 'Une ligne par client LCSI : business model, groupe client, groupement, manager N+1, commercial.',
            ],
            'facturation' => [
                'title'  => 'Facturation : commandé, facturé et reste à facturer',
                'source' => 'Vue SEI LCS_X3SALESX (BOH_*, INV_*, ROB_*)',
                'file'   => 'Sei/modules/suivi_sellin/03_facturation_rdd.sql',
                'note'   => 'Par saison, type de commande, famille, client, mois de facture et mois de livraison demandée (RDD) (environ 108 000 lignes).',
            ],
            'reste_a_livrer' => [
                'title'  => 'Reste à livrer par date de livraison demandée',
                'source' => 'Vue SEI LCS_X3SALESX (ROB_*)',
                'file'   => 'Sei/modules/suivi_sellin/11_reste_a_livrer_rdd.sql',
                'note'   => 'Montant et quantité restant à facturer, par client, article et date RDD (environ 40 000 lignes).',
            ],
            'backlog' => [
                'title'  => 'Backlog client (reste à livrer avec dates)',
                'source' => 'X3 (mêmes règles que la stat Backlog Clients)',
                'file'   => 'Sei/modules/suivi_sellin/09_backlog_client.sql',
                'note'   => 'Lignes de commande ouvertes regroupées par client, article, collection, classe et dates (environ 29 000 lignes). Montant converti en euros côté PHP.',
            ],
            'backlog_commandes' => [
                'title'  => 'Nombre de commandes ouvertes par client',
                'source' => 'X3 (mêmes règles que la stat Backlog Clients)',
                'file'   => 'Sei/modules/suivi_sellin/10_backlog_commandes_par_client.sql',
                'note'   => 'Une ligne par client ayant au moins une commande ouverte.',
            ],
            'remises' => [
                'title'  => 'Remises clients et conditions de paiement',
                'source' => 'Vue MASTER_TABLES.PRODXXX_CLIENT_INTRANE (stat Comptes Clients)',
                'file'   => 'Sei/modules/suivi_sellin/05_remises_clients.sql',
                'note'   => 'Remplace l\'Excel Comptes_Clients_003_avec_REMISE_SS27.xlsx du template. Une colonne par saison.',
            ],
            'avoirs' => [
                'title'  => 'Avoirs (accompagnement commercial)',
                'source' => 'Cube SEI_X3_LCS.CONSO_INVOICES',
                'file'   => 'Sei/modules/suivi_sellin/06_avoirs.sql',
                'note'   => 'Avoirs = DOCUMENTTYPE CREDITMEMO, société LCSI, depuis 2024 (environ 120 000 lignes, ~20 s de lecture : lus une fois par la commande de rafraîchissement). Catégories et objet déduits de REASONCODE, ISDECOTE et du code article.',
            ],
            'annulations' => [
                'title'  => 'Annulations de commandes (par client, saison et classe)',
                'source' => 'X3 (règles de la stat État des commandes clients)',
                'file'   => 'Sei/modules/suivi_sellin/12_annulations.sql',
                'note'   => 'Lignes soldées sans livraison (QUANTITE_ANNULEE) et pièces livrées, converties en euros côté PHP. Le commandé de comparaison vient du carnet SEI.',
            ],
            'annulations_articles' => [
                'title'  => 'Références les plus annulées',
                'source' => 'X3 (règles de la stat État des commandes clients)',
                'file'   => 'Sei/modules/suivi_sellin/13_annulations_articles.sql',
                'note'   => 'Une ligne par client, collection et article ayant une quantité annulée.',
            ],
            'reassort' => [
                'title'  => 'Commandes de réassort (type RE) : commandé et facturé',
                'source' => 'Vue SEI LCS_X3SALESX',
                'file'   => 'Sei/modules/suivi_sellin/04_reassort.sql',
                'note'   => 'Par saison, famille, client, mois de facture et semaine de livraison demandée (environ 34 000 lignes). Le RAL magasin et le réassort conseillé du sell-out viennent des fichiers Push Réassort (voir l\'onglet Sell-out).',
            ],
            'sellout_hebdo' => [
                'title'  => 'Sell-out hebdomadaire : ventes et stock magasin',
                'source' => 'Cube SEI_X3_LCS.LCS_SELLOUT_SALES',
                'file'   => 'Sei/modules/suivi_sellin/07_sellout_hebdo.sql',
                'note'   => 'Intersport et Sport 2000, restreint aux articles du reporting Push Réassort de chaque saison (une requête par saison et enseigne ; le cube contient bien plus d\'articles que la collection suivie).',
            ],
            'sellout_ref' => [
                'title'  => 'Sell-out par référence',
                'source' => 'Cube SEI_X3_LCS.LCS_SELLOUT_SALES',
                'file'   => 'Sei/modules/suivi_sellin/08_sellout_reference.sql',
                'note'   => 'Ventes cumulées de la saison, stock de la semaine de référence et ventes des dernières semaines, par magasin et article (mêmes articles que la requête hebdomadaire).',
            ],
            'sellout_fichiers' => [
                'title'  => 'Sell-out : fichiers Push Réassort (SharePoint)',
                'source' => 'Fichiers Excel SharePoint, lecture seule',
                'file'   => 'Sei/modules/suivi_sellin/14_sellout_fichiers_push.sql',
                'note'   => 'Liste d\'articles de la saison, RAL, préparation, valeur du stock et informations des magasins des saisons en cours ; réassort conseillé recalculé avec la formule du fichier.',
            ],
        ];
    }

    /**
     * Requêtes dont dépend la fiche magasin (ouverte depuis la plupart des onglets) : elle reprend le carnet, la facturation,
     * le reste à livrer, le backlog, les remises, les avoirs, les annulations et le sell-out du magasin ou du groupement.
     */
    private const FICHE_QUERIES = ['carnet', 'clients', 'facturation', 'reassort', 'reste_a_livrer', 'backlog', 'remises', 'avoirs', 'annulations', 'annulations_articles', 'sellout_hebdo', 'sellout_ref', 'sellout_fichiers'];

    /**
     * @return list<array{key:string, title:string, subtitle:string, queries:list<string>}>
     */
    public function menus(): array
    {
        $all = array_keys($this->queries());

        return array_map(static function (array $menu) use ($all): array {
            $menu['queries'] = $menu['key'] === 'infos'
                ? $all
                : array_values(array_unique([...$menu['queries'], ...self::FICHE_QUERIES]));

            return $menu;
        }, $this->baseMenus());
    }

    /** @return list<array{key:string, title:string, subtitle:string, queries:list<string>}> requêtes propres à chaque onglet */
    private function baseMenus(): array
    {
        return [
            ['key' => 'accueil',   'title' => 'Accueil',                       'subtitle' => 'Mon cockpit commercial',               'queries' => ['carnet', 'clients', 'backlog', 'remises']],
            ['key' => 'synthese',  'title' => 'Synthèse N vs N-1',             'subtitle' => 'Tableau croisé, comparable, écarts',   'queries' => ['carnet', 'clients']],
            ['key' => 'clients',   'title' => 'Vue centrale',                  'subtitle' => 'Comparable par groupe et groupement',  'queries' => ['carnet', 'clients']],
            ['key' => 'alertes',   'title' => 'Alertes',                       'subtitle' => 'Clients et lignes à surveiller',       'queries' => ['carnet', 'backlog', 'clients']],
            ['key' => 'magasins',  'title' => 'Magasins',                      'subtitle' => 'Recherche, hausses et baisses',        'queries' => ['carnet', 'clients']],
            ['key' => 'portes',    'title' => 'Portes perdues / nouvelles',    'subtitle' => 'Portes à reconquérir',                 'queries' => ['carnet', 'clients']],
            ['key' => 'fact',      'title' => 'Facturation',                   'subtitle' => 'Facturé et reste à livrer (RDD)',      'queries' => ['facturation', 'reste_a_livrer', 'backlog', 'backlog_commandes', 'clients']],
            ['key' => 'accomp',    'title' => 'Accompagnement commercial',     'subtitle' => 'Décotes, retours, avoirs, annulations', 'queries' => ['avoirs', 'annulations', 'annulations_articles', 'carnet', 'clients']],
            ['key' => 'sellout',   'title' => 'Sell-out',                      'subtitle' => 'Ventes et stocks magasins par semaine', 'queries' => ['sellout_hebdo', 'sellout_ref', 'sellout_fichiers']],
            ['key' => 'reassort',  'title' => 'Pré-saison / réassort',         'subtitle' => 'Magasins, pièces, rythme',             'queries' => ['reassort', 'carnet', 'clients']],
            ['key' => 'saisons',   'title' => 'Évolution saisons',             'subtitle' => 'Historique sur 8 saisons',             'queries' => ['carnet', 'clients']],
            ['key' => 'diffusion', 'title' => 'Diffusion de l\'offre',         'subtitle' => 'Part des portes par référence',        'queries' => ['carnet', 'clients']],
            ['key' => 'infos',     'title' => 'Données & définitions',         'subtitle' => 'Sources et règles de calcul',          'queries' => ['remises']],
        ];
    }

    /**
     * Menus complétés du texte SQL de chaque requête, prêts à afficher.
     *
     * @return list<array{key:string, title:string, subtitle:string, queries:list<array{title:string, source:string, file:string, note:string, sql:string}>}>
     */
    public function menusWithSql(): array
    {
        $queries = $this->queries();
        $sql = [];
        foreach ($queries as $id => $query) {
            $sql[$id] = trim($this->sqlFileLoader->load($query['file']));
        }

        return array_map(
            static fn (array $menu): array => [
                'key'      => $menu['key'],
                'title'    => $menu['title'],
                'subtitle' => $menu['subtitle'],
                'queries'  => array_map(
                    static fn (string $id): array => $queries[$id] + ['sql' => $sql[$id]],
                    $menu['queries'],
                ),
            ],
            $this->menus(),
        );
    }

    /**
     * Onglets livrés (clés du template) : les autres sont masqués tant que leurs données ne sont pas branchées.
     * Le module est livré onglet par onglet, chacun validé par le Sales Manager avant le suivant.
     *
     * @return list<string>
     */
    public function enabledTabs(): array
    {
        return ['accueil', 'synthese', 'clients', 'alertes', 'magasins', 'portes', 'saisons', 'diffusion', 'fact', 'reassort', 'accomp', 'sellout', 'infos'];
    }

    /**
     * Requêtes de chaque onglet, indexées par clé d'onglet, pour le bouton « Requêtes » de l'application.
     *
     * @return array<string, list<array{title:string, source:string, file:string, note:string, sql:string}>>
     */
    public function queriesByTab(): array
    {
        $out = [];
        foreach ($this->menusWithSql() as $menu) {
            $out[$menu['key']] = $menu['queries'];
        }

        return $out;
    }
}
