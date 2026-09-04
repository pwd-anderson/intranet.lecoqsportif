<?php

namespace App\Service;

final class StatRegistry
{
    /**
     * Liste centrale de toutes les stats de l'intranet.
     * section    : clé de la section sidebar
     * label      : libellé affiché dans la page admin
     * roles      : rôles qui ont accès à cette section (pour grisage dans l'admin)
     */
    public static function all(): array
    {
        return [
            // ── Ventes ────────────────────────────────────────────────────
            'app_kpi_retail'                              => ['section' => 'Ventes',  'label' => 'KPI Retail Deck',                   'roles' => ['ROLE_SALES', 'ROLE_MARKETING']],
            'app_kpi_retail_x3'                           => ['section' => 'Ventes',  'label' => 'KPI Retail X3',                     'roles' => ['ROLE_SALES', 'ROLE_MARKETING']],
            'app_sales_reassort'                          => ['section' => 'Ventes',  'label' => 'Reassort magasin',                  'roles' => ['ROLE_SALES', 'ROLE_MARKETING']],
            'app_sales_excess_for_sales'                  => ['section' => 'Ventes',  'label' => 'Excess for sales',                  'roles' => ['ROLE_SALES', 'ROLE_MARKETING']],
            'app_sales_poid_famille_par_variant'          => ['section' => 'Ventes',  'label' => 'Poids / tailles',                   'roles' => ['ROLE_SALES', 'ROLE_MARKETING']],
            'app_sales_best_demand_per_style'             => ['section' => 'Ventes',  'label' => 'Best demand per style',             'roles' => ['ROLE_SALES', 'ROLE_MARKETING']],
            'app_sales_sell_in_suivi_ps'                  => ['section' => 'Ventes',  'label' => 'Sell in suivi PS',                  'roles' => ['ROLE_SALES', 'ROLE_MARKETING']],
            'app_sales_suivi_perf_wholesale_fr'           => ['section' => 'Ventes',  'label' => 'Suivi perf wholesale FR',           'roles' => ['ROLE_SALES', 'ROLE_MARKETING']],
            'app_sales_ventes_qte_ca_client'              => ['section' => 'Ventes',  'label' => 'Ventes qté CA client',              'roles' => ['ROLE_SALES', 'ROLE_MARKETING']],

            // ── ADV ───────────────────────────────────────────────────────
            'app_sales_livraison_non_facturees'           => ['section' => 'ADV',     'label' => 'Livraisons non facturées',          'roles' => ['ROLE_ADV']],
            'app_sales_backlog_clients_x3'                => ['section' => 'ADV',     'label' => 'Backlog Clients X3',                'roles' => ['ROLE_ADV']],
            'app_sales_commandes_a_facturer_x3'           => ['section' => 'ADV',     'label' => 'Commandes à facturer X3',           'roles' => ['ROLE_ADV']],
            'app_sales_etat_commandes_clients_x3'         => ['section' => 'ADV',     'label' => 'État des commandes clients',        'roles' => ['ROLE_ADV']],

            // ── Achats ────────────────────────────────────────────────────
            'app_backlog_fournisseur_x3'                  => ['section' => 'Achats',  'label' => 'Backlog fournisseur X3',            'roles' => ['ROLE_PURCHASING']],
            'app_reception_fournisseur'                   => ['section' => 'Achats',  'label' => 'Réception fournisseur',             'roles' => ['ROLE_PURCHASING']],

            // ── Stock ─────────────────────────────────────────────────────
            'app_stock_a_terme_x3'                        => ['section' => 'Stock',   'label' => 'Stock à terme',                     'roles' => ['ROLE_LOGISTIC', 'ROLE_PURCHASING']],
            'app_stock_a_terme_segmentation_produits_x3'  => ['section' => 'Stock',   'label' => 'Segmentation produits',             'roles' => ['ROLE_LOGISTIC', 'ROLE_PURCHASING']],
            'app_stock_allocation'                        => ['section' => 'Stock',   'label' => 'Allocation',                        'roles' => ['ROLE_LOGISTIC', 'ROLE_PURCHASING']],
            'app_stock_composant'                         => ['section' => 'Stock',   'label' => 'Composant',                         'roles' => ['ROLE_LOGISTIC', 'ROLE_PURCHASING']],
            'app_stock_produits'                          => ['section' => 'Stock',   'label' => 'Produits',                          'roles' => ['ROLE_LOGISTIC', 'ROLE_PURCHASING']],
            'app_stock_produits_shop_non_coches'          => ['section' => 'Stock',   'label' => 'Produits shop non cochés',          'roles' => ['ROLE_LOGISTIC', 'ROLE_PURCHASING']],
            'app_stock_logtex'                            => ['section' => 'Stock',   'label' => 'Stock LogTex',                      'roles' => ['ROLE_LOGISTIC', 'ROLE_PURCHASING']],

            // ── IT ────────────────────────────────────────────────────────
            'app_it_tcd_cmd_non_soldees'                  => ['section' => 'IT',      'label' => 'TCD commandes non soldées',         'roles' => ['ROLE_IT']],
            'app_it_detail_tcd'                           => ['section' => 'IT',      'label' => 'Détail TCD',                        'roles' => ['ROLE_IT']],

            // ── SAV ───────────────────────────────────────────────────────
            'app_it_suivi_commandes_web_sav'              => ['section' => 'SAV',     'label' => 'Suivi commandes web SAV',           'roles' => ['ROLE_SAV']],

            // ── Dashboard accueil — filtres réseau ────────────────────────
            'dashboard_filter_global'                     => ['section' => 'Accueil', 'label' => 'Filtre Global',                     'roles' => []],
            'dashboard_filter_boutique'                   => ['section' => 'Accueil', 'label' => 'Filtre Boutique',                   'roles' => []],
            'dashboard_filter_ecom'                       => ['section' => 'Accueil', 'label' => 'Filtre E-com',                     'roles' => []],
            'dashboard_filter_wholesale_fr'               => ['section' => 'Accueil', 'label' => 'Filtre Wholesale France',           'roles' => []],
            'dashboard_filter_wholesale_eu'               => ['section' => 'Accueil', 'label' => 'Filtre Wholesale Europe',           'roles' => []],
            'dashboard_filter_wholesale_int'              => ['section' => 'Accueil', 'label' => 'Filtre Wholesale International',    'roles' => []],

            // ── Dashboard Sell Out ────────────────────────────────────────
            'app_dashboard_sellout'                       => ['section' => 'Accueil', 'label' => 'Dashboard Sell Out',               'roles' => []],

            // ── Modules ───────────────────────────────────────────────────
            'module_etiquettes'                           => ['section' => 'Modules', 'label' => 'Étiquettes expédition',             'roles' => []],
            'module_backlog_commandes'                    => ['section' => 'Modules', 'label' => 'Backlog commandes',                 'roles' => []],
            'module_import_od'                            => ['section' => 'Modules', 'label' => 'Import OD',                        'roles' => []],
        ];
    }

    /** Retourne les stats groupées par section */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::all() as $key => $stat) {
            $grouped[$stat['section']][$key] = $stat;
        }
        return $grouped;
    }
}
