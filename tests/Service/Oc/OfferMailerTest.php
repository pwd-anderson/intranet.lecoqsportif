<?php

namespace App\Tests\Service\Oc;

use App\Entity\Oc\Offer;
use App\Entity\Oc\OfferLine;
use App\Entity\User;
use App\Service\Oc\OfferMailer;
use App\Service\Tools\GraphMailer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OfferMailerTest extends TestCase
{
    private GraphMailer&MockObject $graph;

    protected function setUp(): void
    {
        $this->graph = $this->createMock(GraphMailer::class);
    }

    private function mailer(string $env, string $headSale = 'head1@lcs.test, head2@lcs.test'): OfferMailer
    {
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('https://intranet.test/fr/oc/42');

        return new OfferMailer($this->graph, $router, $this->createStub(LoggerInterface::class), $headSale, 'admin@lcs.test', $env);
    }

    private function offer(?string $creatorEmail = 'alice@lcs.test', string $creatorName = 'Alice Martin'): Offer
    {
        $user = $this->createStub(User::class);
        $user->method('getEmail')->willReturn($creatorEmail);

        $offer = (new Offer())
            ->setNumber('OC2026102054812345678')
            ->setCustomerCode('20548')->setCustomerName('T2 SPORT SAS')
            ->setStartDate(new \DateTimeImmutable('2026-11-01'))->setEndDate(new \DateTimeImmutable('2027-10-31'))
            ->setCreatedBy($user)->setCreatedByName($creatorName);
        $offer->addLine(new OfferLine());

        return $offer;
    }

    /** @return list<string> */
    private static function addresses(Email $mail): array
    {
        return array_map(static fn ($a) => $a->getAddress(), $mail->getTo());
    }

    public function testInProductionTheHeadSaleReceivesTheSubmissionNotice(): void
    {
        $sent = null;
        $this->graph->expects(self::once())->method('send')->willReturnCallback(function (Email $m) use (&$sent): void { $sent = $m; });

        self::assertTrue($this->mailer('prod')->notifySubmitted($this->offer()));

        self::assertSame(['head1@lcs.test', 'head2@lcs.test'], self::addresses($sent));
        self::assertStringContainsString('OC2026102054812345678', $sent->getSubject());
        self::assertStringNotContainsString('[DEV', $sent->getSubject());
        $html = $sent->getHtmlBody();
        self::assertStringContainsString('https://intranet.test/fr/oc/42', $html);
        self::assertStringContainsString('T2 SPORT SAS', $html);
        self::assertStringContainsString('Alice Martin', $html);
    }

    public function testInDevEveryMailGoesToTheAdminOnlyAndTheIntendedRecipientIsInTheSubject(): void
    {
        $sent = null;
        $this->graph->expects(self::once())->method('send')->willReturnCallback(function (Email $m) use (&$sent): void { $sent = $m; });

        $this->mailer('dev')->notifySubmitted($this->offer());

        self::assertSame(['admin@lcs.test'], self::addresses($sent), 'jamais aux vrais destinataires en dev');
        self::assertStringStartsWith('[DEV → prévu : head1@lcs.test, head2@lcs.test]', $sent->getSubject());
        self::assertSame([], $sent->getCc());
        self::assertSame([], $sent->getBcc());
    }

    public function testInDevTheRejectionToTheCreatorAlsoGoesToTheAdmin(): void
    {
        $sent = null;
        $this->graph->method('send')->willReturnCallback(function (Email $m) use (&$sent): void { $sent = $m; });

        $this->mailer('dev')->notifyRejected($this->offer(), 'Motif');

        self::assertSame(['admin@lcs.test'], self::addresses($sent));
        self::assertStringContainsString('prévu : alice@lcs.test', $sent->getSubject());
    }

    public function testInTestEnvironmentNothingIsEverSent(): void
    {
        $this->graph->expects(self::never())->method('send');

        self::assertFalse($this->mailer('test')->notifySubmitted($this->offer()));
        self::assertFalse($this->mailer('test')->notifyRejected($this->offer(), 'Motif'));
    }

    public function testRejectionGoesToTheCreatorWithTheEscapedReason(): void
    {
        $sent = null;
        $this->graph->method('send')->willReturnCallback(function (Email $m) use (&$sent): void { $sent = $m; });

        $this->mailer('prod')->notifyRejected($this->offer(), "Prix <b>trop</b> bas\nvoir direction");

        self::assertSame(['alice@lcs.test'], self::addresses($sent));
        $html = $sent->getHtmlBody();
        self::assertStringContainsString('Prix &lt;b&gt;trop&lt;/b&gt; bas<br />', $html);
        self::assertStringNotContainsString('<b>trop</b>', $html, 'le motif ne doit pas injecter de HTML');
    }

    public function testCreatorNameIsEscapedInTheMail(): void
    {
        $sent = null;
        $this->graph->method('send')->willReturnCallback(function (Email $m) use (&$sent): void { $sent = $m; });

        $this->mailer('prod')->notifySubmitted($this->offer('alice@lcs.test', '<script>alert(1)</script>'));

        self::assertStringNotContainsString('<script>', $sent->getHtmlBody());
    }

    public function testNothingIsSentWithoutARecipient(): void
    {
        $this->graph->expects(self::never())->method('send');

        self::assertFalse($this->mailer('prod', ' , ')->notifySubmitted($this->offer()), 'MAIL_HEAD_SALE vide');
        self::assertFalse($this->mailer('prod')->notifyRejected($this->offer(null), 'Motif'), 'créateur sans email');
    }

    public function testASendingFailureNeverBreaksTheBusinessAction(): void
    {
        $this->graph->method('send')->willThrowException(new \RuntimeException('Graph indisponible'));
        $this->graph->expects(self::once())->method('notifyError');

        self::assertFalse($this->mailer('prod')->notifySubmitted($this->offer()), 'pas d\'exception, juste false');
    }

    public function testValidationMailGoesToTheCreatorWithThePdfAttached(): void
    {
        $sent = null;
        $this->graph->expects(self::once())->method('send')->willReturnCallback(function (Email $m) use (&$sent): void { $sent = $m; });

        self::assertTrue($this->mailer('prod')->notifyValidated($this->offer(), '%PDF-contenu', 'OC_X.pdf'));

        self::assertSame(['alice@lcs.test'], self::addresses($sent));
        self::assertStringContainsString('validée', $sent->getSubject());
        self::assertCount(1, $sent->getAttachments());
        $part = $sent->getAttachments()[0];
        self::assertSame('OC_X.pdf', $part->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename'));
        self::assertSame('%PDF-contenu', $part->getBody());
    }

    public function testInDevTheValidationMailGoesToTheAdminWithThePdf(): void
    {
        $sent = null;
        $this->graph->method('send')->willReturnCallback(function (Email $m) use (&$sent): void { $sent = $m; });

        $this->mailer('dev')->notifyValidated($this->offer(), '%PDF', 'OC_X.pdf');

        self::assertSame(['admin@lcs.test'], self::addresses($sent));
        self::assertStringContainsString('prévu : alice@lcs.test', $sent->getSubject());
        self::assertCount(1, $sent->getAttachments());
    }

    public function testInTestEnvironmentTheValidationMailIsNeverSentAndTheMailerIsDisabled(): void
    {
        $this->graph->expects(self::never())->method('send');

        self::assertFalse($this->mailer('test')->notifyValidated($this->offer(), '%PDF', 'OC_X.pdf'));
        self::assertFalse($this->mailer('test')->isEnabled());
        self::assertTrue($this->mailer('prod')->isEnabled());
    }
    public function testTheMailCountsArticlesNotSizesAndMentionsSizeLines(): void
    {
        $offer = $this->offer();
        $offer->clearLines();
        foreach (['2210037', '2210037_T9', '2210037_T10', '1511960'] as $code) {
            $offer->addLine((new OfferLine())->setArticleCode($code));
        }
        $sent = null;
        $this->graph->method('send')->willReturnCallback(function (Email $m) use (&$sent): void { $sent = $m; });

        $this->mailer('prod')->notifySubmitted($offer);

        self::assertStringContainsString('2 (dont 2 lignes de taille)', (string) $sent->getHtmlBody());
    }
}
