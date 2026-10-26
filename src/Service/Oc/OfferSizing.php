<?php

namespace App\Service\Oc;

use App\Entity\Oc\OfferLine;

/**
 * Règle « parent / taille » d'une OC, SEULE source de vérité pour les flux X3, la vue de validation et le PDF.
 *
 * Une ligne est un article PARENT (« 2210037 » : son prix vaut pour toutes ses tailles) ou une TAILLE (« 2210037_T9 » : ce prix ne
 * vaut que pour cette taille et l'emporte sur celui du parent). Un parent envoie un flux à chacun de ses variants SAUF ceux qui ont
 * leur propre ligne ; une taille seule ne tarife que cette taille. Voir CLAUDE.md, « Prix par taille ».
 */
final class OfferSizing
{
    /**
     * Ce qui part à X3 : un flux par variant, avec la ligne dont il prend le prix.
     *
     * @param list<OfferLine> $lines
     * @param array<string, list<string>> $variants variants connus de X3, par code parent
     *
     * @return array{flows: list<array{variant:string,line:OfferLine,own:bool}>, problems: list<array{code:string,message:string}>}
     *         own = vrai quand la taille a sa propre ligne ; problems = lignes qui ne peuvent pas partir (aucun flux pour elles)
     */
    public static function plan(array $lines, array $variants): array
    {
        $explicit = [];
        foreach ($lines as $line) {
            if ($line->isVariant()) {
                $explicit[$line->getArticleCode()] = true;
            }
        }

        $flows = [];
        $problems = [];
        $seen = [];
        foreach ($lines as $line) {
            $children = $variants[$line->getParentCode()] ?? [];
            if ($line->isVariant()) {
                if (!in_array($line->getArticleCode(), $children, true)) {
                    $problems[] = ['code' => $line->getArticleCode(), 'message' => sprintf('Taille %s introuvable pour l\'article parent %s', $line->getArticleCode(), $line->getParentCode())];
                    continue;
                }
                $targets = [$line->getArticleCode()];
            } else {
                if ($children === []) {
                    $problems[] = ['code' => $line->getArticleCode(), 'message' => sprintf('Aucun variant trouvé pour l\'article parent %s', $line->getArticleCode())];
                    continue;
                }
                $targets = array_values(array_filter($children, static fn (string $v): bool => !isset($explicit[$v])));
            }
            foreach ($targets as $child) {
                if (isset($seen[$child])) {
                    continue; // jamais deux fois le même variant
                }
                $seen[$child] = true;
                $flows[] = ['variant' => $child, 'line' => $line, 'own' => $line->isVariant()];
            }
        }

        return ['flows' => $flows, 'problems' => $problems];
    }

    /**
     * Comme plan(), mais lève l'exception de génération des flux au premier problème (rien ne doit partir si une ligne est en défaut).
     *
     * @param list<OfferLine> $lines
     * @param array<string, list<string>> $variants
     *
     * @return list<array{variant:string,line:OfferLine,own:bool}>
     *
     * @throws \RuntimeException
     */
    public static function assign(array $lines, array $variants): array
    {
        $plan = self::plan($lines, $variants);
        if ($plan['problems'] !== []) {
            throw new \RuntimeException($plan['problems'][0]['message'] . ' : flux X3 non généré.');
        }

        return $plan['flows'];
    }

    /**
     * Lignes regroupées par article, dans l'ordre de saisie : la ligne du parent d'abord, puis ses tailles (triées par code).
     * Si $variants est fourni (lecture X3 réussie), chaque groupe porte aussi « sent » : ce qui part à X3 pour cet article.
     *
     * @param list<OfferLine> $lines
     * @param array<string, list<string>>|null $variants null = tailles de X3 non lues
     *
     * @return list<array{
     *   parent:string, parentLine:?OfferLine, sizeLines:list<OfferLine>, lines:list<OfferLine>, sizeLabels:list<string>,
     *   sent:?array{total:int, own:list<array{variant:string,size:string,line:OfferLine}>, rest:int, restLine:?OfferLine, problems:list<string>}
     * }>
     */
    public static function groups(array $lines, ?array $variants = null): array
    {
        $groups = [];
        foreach ($lines as $line) {
            $parent = $line->getParentCode();
            $groups[$parent] ??= ['parent' => $parent, 'parentLine' => null, 'sizeLines' => []];
            if ($line->isVariant()) {
                $groups[$parent]['sizeLines'][] = $line;
            } else {
                $groups[$parent]['parentLine'] = $line;
            }
        }

        $plan = $variants !== null ? self::plan($lines, $variants) : null;

        $out = [];
        foreach ($groups as $g) {
            usort($g['sizeLines'], static fn (OfferLine $a, OfferLine $b): int => strcmp($a->getArticleCode(), $b->getArticleCode()));
            $g['lines'] = array_values(array_filter([$g['parentLine'], ...$g['sizeLines']]));
            $g['sizeLabels'] = array_map(static fn (OfferLine $l): string => self::sizeOf($l->getArticleCode()), $g['sizeLines']);
            $g['sent'] = null;
            if ($plan !== null) {
                $own = [];
                $rest = 0;
                $restLine = null;
                foreach ($plan['flows'] as $f) {
                    if ($f['line']->getParentCode() !== $g['parent']) {
                        continue;
                    }
                    if ($f['own']) {
                        $own[] = ['variant' => $f['variant'], 'size' => self::sizeOf($f['variant']), 'line' => $f['line']];
                    } else {
                        ++$rest;
                        $restLine = $f['line'];
                    }
                }
                usort($own, static fn (array $a, array $b): int => strcmp($a['variant'], $b['variant']));
                $g['sent'] = [
                    'total' => count($own) + $rest,
                    'own' => $own,
                    'rest' => $rest,
                    'restLine' => $restLine,
                    'problems' => array_values(array_map(
                        static fn (array $p): string => $p['message'],
                        array_filter($plan['problems'], static fn (array $p): bool => OfferLine::parentOf($p['code']) === $g['parent']),
                    )),
                ];
            }
            $out[] = $g;
        }

        return $out;
    }

    /** Taille d'un code de variant : « 2210037_T9 » → « T9 » (un parent n'a pas de taille : chaîne vide). */
    public static function sizeOf(string $articleCode): string
    {
        $parent = OfferLine::parentOf($articleCode);

        return strlen($articleCode) > strlen($parent) ? substr($articleCode, strlen($parent) + 1) : '';
    }
}
