<?php

namespace App\Tests\Service\Oc;

use App\Entity\Oc\Offer;
use App\Entity\Oc\OfferLine;
use App\Entity\User;
use App\Service\Oc\OcX3Lookup;
use App\Service\Oc\OfferPdfGenerator;
use App\Service\Tools\LecoqsportifImageFetcher;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/** Contenu et génération réelle (Dompdf) du PDF d'offre ; X3 et photos simulés. */
final class OfferPdfGeneratorTest extends KernelTestCase
{
    private function generator(): OfferPdfGenerator
    {
        self::bootKernel();

        $x3 = $this->createStub(OcX3Lookup::class);
        $x3->method('getCustomerAddress')->willReturn(['LIGNE_1' => 'ROUTE DE PAU', 'LIGNE_2' => '', 'LIGNE_3' => '', 'CODE_POSTAL' => '65420', 'VILLE' => 'IBOS', 'PAYS' => 'France']);
        $images = $this->createStub(LecoqsportifImageFetcher::class);
        $images->method('fetchAsBase64')->willReturn(null);

        return new OfferPdfGenerator(
            static::getContainer()->get('twig'), $images, $x3, new ArrayAdapter(), new NullLogger(), static::getContainer()->getParameter('kernel.project_dir')
        );
    }

    private function offer(): Offer
    {
        $user = $this->createStub(User::class);
        $user->method('getEmail')->willReturn('alice@lcs.test');

        $line = (new OfferLine())->setArticleCode('2210037')->setArticleLabel('EFRO 22 Gants')->setCollection('2026-01-SS')
            ->setTariff('18.50')->setMsrp('39.00')->setMargin('43.08')->setMinQty(0)->setMaxQty(999999)
            ->setX3Tariff('19.50')->setInjectedPrice('21.26')->setInjectedDiscount('13.00')->setPurchasePrice('4.84')->setPurchaseCurrency('USD');
        $offer = (new Offer())
            ->setNumber('OC2026102054812345678')->setCustomerCode('20548')->setCustomerName('T2 SPORT SAS')->setCustomerCurrency('EUR')
            ->setStartDate(new \DateTimeImmutable('2027-02-01'))->setEndDate(new \DateTimeImmutable('2027-12-31'))
            ->setCreatedBy($user)->setCreatedByName('Alice Martin');
        $offer->addLine($line);

        return $offer;
    }

    public function testHtmlShowsTheOfferButNeverThePriceSentToX3NorInternalX3Values(): void
    {
        $html = $this->generator()->renderHtml($this->offer());

        foreach (['OC2026102054812345678', 'T2 SPORT SAS', 'IBOS', 'ALICE MARTIN', '2210037', 'EFRO 22 Gants', '18,50', '39,00', '43,08', '01/02/2027', '31/12/2027'] as $expected) {
            self::assertStringContainsString($expected, $html);
        }
        foreach (['21,26', '21.26', '19,50', '19.50', '4,84', '4.84', 'Marge LCS'] as $secret) {
            self::assertStringNotContainsString($secret, $html, "« $secret » ne doit jamais figurer sur le PDF client");
        }
    }

    public function testGeneratesARealPdfAndNamesItAfterTheOffer(): void
    {
        $generator = $this->generator();
        $offer = $this->offer();

        self::assertSame('OC_OC2026102054812345678.pdf', $generator->filename($offer));
        self::assertStringStartsWith('%PDF', $generator->generate($offer));
    }
    public function testSizeLinesAreGroupedUnderTheirArticleWithASinglePhotoCell(): void
    {
        $offer = $this->offer();
        $offer->addLine((new OfferLine())->setArticleCode('2210037_T9')->setArticleLabel('EFRO 22 Gants')->setTariff('15.00')->setMsrp('39.00')->setMargin('61.54')
            ->setMinQty(5)->setMaxQty(50)->setInjectedPrice('17.24')->setInjectedDiscount('13.00'));
        $offer->addLine((new OfferLine())->setArticleCode('1511960')->setArticleLabel('Polo')->setTariff('30.00')->setMinQty(0)->setMaxQty(999999)->setInjectedPrice('34.48'));

        $html = $this->generator()->renderHtml($offer);

        self::assertStringContainsString('TAILLE T9', $html);
        self::assertStringContainsString('Toutes les tailles à ce prix, sauf : T9', $html);
        self::assertStringContainsString('15,00', $html, 'le prix de la taille figure sur le PDF');
        self::assertSame(2, substr_count($html, 'class="nophoto"'), 'une cellule photo par article (2210037 et 1511960), pas une par taille');
        self::assertLessThan(strpos($html, '1511960'), strpos($html, 'TAILLE T9'), 'la taille suit son article, avant l\'article suivant');
        foreach (['17,24', '17.24', '34,48'] as $secret) {
            self::assertStringNotContainsString($secret, $html, 'le prix envoyé à X3 ne figure jamais sur le PDF client');
        }
        self::assertStringStartsWith('%PDF', $this->generator()->generate($offer));
    }

    public function testASizeAloneIsLabelledAsThatSizeOnly(): void
    {
        $offer = $this->offer();
        $offer->clearLines();
        $offer->addLine((new OfferLine())->setArticleCode('2210037_T9')->setArticleLabel('EFRO 22 Gants')->setTariff('15.00')->setMinQty(0)->setMaxQty(999999)->setInjectedPrice('17.24'));

        $html = $this->generator()->renderHtml($offer);

        self::assertStringContainsString('TAILLE T9', $html);
        self::assertStringContainsString('Cette taille uniquement', $html);
    }
}
