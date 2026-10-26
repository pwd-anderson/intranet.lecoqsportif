<?php

namespace App\Tests\Service\Oc;

use App\Entity\Oc\OfferLine;
use App\Service\Oc\OfferSizing;
use PHPUnit\Framework\TestCase;

final class OfferSizingTest extends TestCase
{
    /** @param array<string,string> $tariffs code article => tarif */
    private function lines(array $tariffs): array
    {
        $out = [];
        foreach ($tariffs as $code => $tariff) {
            $out[] = (new OfferLine())->setArticleCode($code)->setTariff($tariff)->setInjectedPrice($tariff);
        }

        return $out;
    }

    public function testParentAloneSendsEverySize(): void
    {
        $plan = OfferSizing::plan($this->lines(['2210037' => '35']), ['2210037' => ['2210037_L', '2210037_M', '2210037_S']]);

        self::assertSame(['2210037_L', '2210037_M', '2210037_S'], array_column($plan['flows'], 'variant'));
        self::assertSame([false, false, false], array_column($plan['flows'], 'own'));
        self::assertSame([], $plan['problems']);
    }

    public function testSizeLineWinsOverItsParentWhateverTheOrder(): void
    {
        $variants = ['2210037' => ['2210037_L', '2210037_M', '2210037_S']];
        foreach ([['2210037' => '35', '2210037_L' => '30'], ['2210037_L' => '30', '2210037' => '35']] as $tariffs) {
            $plan = OfferSizing::plan($this->lines($tariffs), $variants);
            $byVariant = [];
            foreach ($plan['flows'] as $f) {
                $byVariant[$f['variant']] = [$f['line']->getTariff(), $f['own']];
            }
            ksort($byVariant);
            self::assertSame(['2210037_L' => ['30.0000', true], '2210037_M' => ['35.0000', false], '2210037_S' => ['35.0000', false]], $byVariant);
        }
    }

    public function testSizeAloneSendsOnlyThatSize(): void
    {
        $plan = OfferSizing::plan($this->lines(['2210037_L' => '30']), ['2210037' => ['2210037_L', '2210037_S']]);

        self::assertSame(['2210037_L'], array_column($plan['flows'], 'variant'));
    }

    public function testProblemsAreListedWithoutStoppingThePlanButAssignRefuses(): void
    {
        $variants = ['2210037' => ['2210037_L'], '1511960' => []];
        $lines = $this->lines(['2210037_XXL' => '30', '1511960' => '20', '2210037_L' => '25']);

        $plan = OfferSizing::plan($lines, $variants);
        self::assertSame(['2210037_L'], array_column($plan['flows'], 'variant'));
        self::assertCount(2, $plan['problems']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('2210037_XXL');
        OfferSizing::assign($lines, $variants);
    }

    public function testGroupsKeepTheEntryOrderAndPutTheParentFirst(): void
    {
        $groups = OfferSizing::groups($this->lines(['2210037_T9' => '30', '1511960' => '20', '2210037' => '35', '2210037_T10' => '43']));

        self::assertSame(['2210037', '1511960'], array_column($groups, 'parent'), 'ordre de première apparition');
        self::assertSame(['2210037', '2210037_T10', '2210037_T9'], array_map(static fn (OfferLine $l): string => $l->getArticleCode(), $groups[0]['lines']), 'parent, puis tailles triées');
        self::assertSame(['T10', 'T9'], $groups[0]['sizeLabels']);
        self::assertNull($groups[0]['sent'], 'sans lecture X3 des tailles, pas de détail d\'envoi');
        self::assertNotNull($groups[1]['parentLine']);
        self::assertSame([], $groups[1]['sizeLines']);
    }

    public function testGroupsSummariseWhatIsSentToX3(): void
    {
        $variants = ['2210037' => ['2210037_L', '2210037_M', '2210037_S', '2210037_T'], '1511960' => ['1511960_40']];

        $groups = OfferSizing::groups($this->lines(['2210037' => '35', '2210037_L' => '30', '2210037_S' => '43', '1511960_40' => '20']), $variants);

        $sent = $groups[0]['sent'];
        self::assertSame(4, $sent['total']);
        self::assertSame(['L', 'S'], array_column($sent['own'], 'size'));
        self::assertSame(2, $sent['rest']);
        self::assertSame('35.0000', $sent['restLine']->getTariff());
        self::assertSame(1, $groups[1]['sent']['total'], 'une taille seule : une seule taille envoyée');
        self::assertSame(0, $groups[1]['sent']['rest']);
    }

    public function testGroupsReportAProblemOnItsOwnArticle(): void
    {
        $groups = OfferSizing::groups($this->lines(['2210037' => '35', '1511960_99' => '20']), ['2210037' => ['2210037_L'], '1511960' => ['1511960_40']]);

        self::assertSame([], $groups[0]['sent']['problems']);
        self::assertStringContainsString('1511960_99', $groups[1]['sent']['problems'][0]);
    }

    public function testSizeLabelHelpers(): void
    {
        self::assertSame('T9', OfferSizing::sizeOf('2210037_T9'));
        self::assertSame('42.5', OfferSizing::sizeOf('2422886_42.5'));
        self::assertSame('', OfferSizing::sizeOf('2210037'));
        self::assertSame('SMST9', (new OfferLine())->setArticleCode('2210037_SMST9')->getSizeLabel());
    }
}
