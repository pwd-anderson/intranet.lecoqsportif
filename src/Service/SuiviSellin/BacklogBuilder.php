<?php

namespace App\Service\SuiviSellin;

use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\BacklogClientV2;
use App\Service\Divers;

/**
 * Jeu « backlog » du template : le reste à livrer (lignes de commande ouvertes), par client / article / collection /
 * classe / dates, avec la date d'arrivée prévue. Requêtes : modules/suivi_sellin/09_backlog_client.sql et
 * 10_backlog_commandes_par_client.sql (mêmes règles que la stat Backlog Clients).
 *
 * La hiérarchie du client (manager, commercial, business model, groupe, groupement) n'est pas relue dans X3 : elle est
 * reprise de celle du jeu principal, pour que les filtres du haut de page (qui comparent des libellés) s'appliquent de
 * la même façon au carnet et au backlog. Les clients absents du jeu principal sont ignorés.
 */
final class BacklogBuilder
{
    private const NR = ColumnarEncoder::NR;
    private const FAMILIES = ['APL' => 'Textile', 'FTW' => 'Footwear', 'HDW' => 'Accessoires', 'SER' => 'Services'];

    public function __construct(
        private readonly SuiviSellinDb $db,
        private readonly SqlFileLoader $sqlFileLoader,
        private readonly BacklogClientV2 $backlogClient,
        private readonly Divers $divers,
    ) {
    }

    /**
     * @param array<string,mixed> $main jeu principal décodé (dicts + attributs par client)
     * @return array<string,mixed>
     */
    public function build(array $main): array
    {
        $custIndex = array_flip($main['dicts']['cust']);
        $rates = $this->divers->getExchangeRatesValues();
        $sql = str_replace(
            '{{PLAN_TRANSPORT_TABLE}}',
            $this->backlogClient->planTransportSource(),
            $this->sqlFileLoader->load('Sei/modules/suivi_sellin/09_backlog_client.sql'),
        );

        $names = ['art', 'col', 'fam', 'typ', 'r1', 'r2', 'bm', 'grp', 'gpm', 'dl', 'da', 'gen'];
        $dict = array_fill_keys($names, []);
        $rows = [];
        $desc = [];
        foreach ($this->db->iterate($sql) as $r) {
            $code = (string) $r['CODE_CLIENT'];
            if (!isset($custIndex[$code])) {
                continue;
            }
            $rate = (float) ($rates[trim((string) $r['DEVISE'])] ?? 0);
            if ($rate <= 0) {
                continue; // devise sans cours : montant inconnu (le Backlog Client affiche 0 dans ce cas)
            }
            $ci = $custIndex[$code];
            $art = trim((string) $r['ARTICLE']);
            $artIdx = ColumnarEncoder::index($dict['art'], $art);
            $desc[$artIdx] ??= trim((string) $r['DESIGNATION']);
            $rows[] = [
                'cust' => $ci,
                'art'  => $artIdx,
                'col'  => ColumnarEncoder::index($dict['col'], trim((string) $r['COLLECTION'])),
                'fam'  => ColumnarEncoder::index($dict['fam'], self::FAMILIES[trim((string) $r['FAMILLE'])] ?? self::NR),
                'typ'  => ColumnarEncoder::index($dict['typ'], $this->clean($r['CLASSE'])),
                'r1'   => ColumnarEncoder::index($dict['r1'], $this->label($main, 'n1', 'custN1I', $ci)),
                'r2'   => ColumnarEncoder::index($dict['r2'], $this->label($main, 'sales', 'custSalesI', $ci)),
                'bm'   => ColumnarEncoder::index($dict['bm'], $this->label($main, 'bm1', 'custBm1I', $ci)),
                'grp'  => ColumnarEncoder::index($dict['grp'], $this->label($main, 'cg', 'custCgI', $ci)),
                'gpm'  => ColumnarEncoder::index($dict['gpm'], $this->label($main, 'grp', 'custGrpI', $ci)),
                'dl'   => ColumnarEncoder::index($dict['dl'], $this->clean($r['DATE_LIVRAISON'])),
                'da'   => ColumnarEncoder::index($dict['da'], trim((string) ($r['DATE_ARRIVEE_PREVUE'] ?? ''))),
                'gen'  => ColumnarEncoder::index($dict['gen'], $this->clean($r['GENRE'])),
                'a'    => round((float) $r['MONTANT_DEVISE'] / $rate, 2),
                'q'    => (int) $r['QUANTITE'],
            ];
        }

        $final = [];
        $maps = [];
        foreach ($dict as $name => $values) {
            [$final[$name], $maps[$name]] = ColumnarEncoder::plainOrder($values);
        }
        $cols = array_fill_keys(['cust', 'art', 'col', 'fam', 'typ', 'r1', 'r2', 'bm', 'grp', 'gpm', 'dl', 'da', 'gen', 'a', 'q'], []);
        foreach ($rows as $row) {
            foreach ($cols as $k => $_) {
                $cols[$k][] = in_array($k, ['cust', 'a', 'q'], true) ? $row[$k] : $maps[$k][$row[$k]];
            }
        }
        $artDesc = array_fill(0, count($final['art']), '');
        foreach ($desc as $provisional => $text) {
            $artDesc[$maps['art'][$provisional]] = $text;
        }

        $orders = [];
        foreach ($this->db->iterate($this->sqlFileLoader->load('Sei/modules/suivi_sellin/10_backlog_commandes_par_client.sql')) as $r) {
            $code = (string) $r['CODE_CLIENT'];
            if (isset($custIndex[$code])) {
                $orders[$custIndex[$code]] = (int) $r['NB_COMMANDES'];
            }
        }

        return [
            'asof'         => date('Y-m-d'),
            'cols'         => $cols,
            'dicts'        => $final + ['artDesc' => $artDesc],
            'ordersByCust' => (object) $orders,
        ];
    }

    /** @param array<string,mixed> $main */
    private function label(array $main, string $dict, string $perCustomer, int $custIndex): string
    {
        return $main['dicts'][$dict][$main[$perCustomer][$custIndex]];
    }

    private function clean(mixed $value): string
    {
        $v = trim((string) ($value ?? ''));

        return $v === '' ? self::NR : $v;
    }
}
