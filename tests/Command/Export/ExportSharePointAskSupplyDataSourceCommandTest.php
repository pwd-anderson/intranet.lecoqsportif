<?php

namespace App\Tests\Command\Export;

use App\Command\Export\ExportSharePointAskSupplyDataSourceCommand;
use App\Factory\MssqlManagerFactory;
use App\Service\Export\AskSupplyDataExporter;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\SharePointClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/** Commande d'export SharePoint : exporteur, SharePoint et mail simulés — rien n'est déposé ni envoyé pour de vrai. */
final class ExportSharePointAskSupplyDataSourceCommandTest extends TestCase
{
    private string $dir;
    private AskSupplyDataExporter&MockObject $exporter;
    private SharePointClient&MockObject $sharePoint;
    private GraphMailer&MockObject $mailer;
    private MssqlManagerFactory&MockObject $factory;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/ask_supply_cmd_' . bin2hex(random_bytes(4));
        mkdir($this->dir);
        $this->exporter = $this->createMock(AskSupplyDataExporter::class);
        $this->sharePoint = $this->createMock(SharePointClient::class);
        $this->mailer = $this->createMock(GraphMailer::class);
        $this->factory = $this->createMock(MssqlManagerFactory::class);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/var/upload/export/ask_supply_data_source/*') ?: [] as $f) {
            @unlink($f);
        }
    }

    private function tester(): CommandTester
    {
        return new CommandTester(new ExportSharePointAskSupplyDataSourceCommand($this->exporter, $this->sharePoint, $this->mailer, $this->factory, $this->dir));
    }

    public function testFailureNeverUploadsThatFileAndOthersContinue(): void
    {
        $this->exporter->method('export')->willReturnCallback(function (string $key, string $target): int {
            if ($key === AskSupplyDataExporter::PRODUITS) {
                return 0;   // requête en échec : le service renvoie []
            }
            file_put_contents($target, 'x');

            return 3;
        });

        $uploaded = [];
        $this->sharePoint->method('upload')->willReturnCallback(function (string $url, string $name) use (&$uploaded): void {
            self::assertStringContainsString('ASK%20SUPPLY%20DATA%20SOURCE', $url);
            $uploaded[] = $name;
        });
        $this->mailer->expects(self::once())->method('notifyError');

        $code = $this->tester()->execute([]);

        self::assertSame(Command::FAILURE, $code);
        self::assertNotContains('Produits.xlsx', $uploaded, 'un export vide ne remplace jamais le fichier précédent');
        self::assertCount(6, $uploaded, 'les 6 autres exports sont déposés');
    }

    public function testDryRunUploadsAndMailsNothing(): void
    {
        $this->exporter->method('export')->willReturnCallback(function (string $key, string $target): int {
            file_put_contents($target, 'x');

            return 1;
        });
        $this->sharePoint->expects(self::never())->method('upload');
        $this->mailer->expects(self::never())->method('notifyError');

        self::assertSame(Command::SUCCESS, $this->tester()->execute(['--dry-run' => true]));
    }

    public function testOnlyRunsTheRequestedExportsAndRejectsUnknownOnes(): void
    {
        $this->exporter->expects(self::once())->method('export')->willReturnCallback(function (string $key, string $target): int {
            self::assertSame(AskSupplyDataExporter::BACKLOG_CLIENTS, $key);
            file_put_contents($target, 'x');

            return 2;
        });
        $this->sharePoint->expects(self::once())->method('upload')->with(self::anything(), 'Backlog_Clients.xlsx', self::anything());

        self::assertSame(Command::SUCCESS, $this->tester()->execute(['--only' => ['backlog-clients']]));
        self::assertSame(Command::INVALID, $this->tester()->execute(['--only' => ['nimporte-quoi']]));
    }

    public function testCollectionsAreFixedInTheCommand(): void
    {
        $this->exporter->method('export')->willReturnCallback(function (string $key, string $target, array $produits, int $etat): int {
            self::assertSame(['2027-01-SS', '2027-02-FW'], $produits);
            self::assertSame(3, $etat);
            file_put_contents($target, 'x');

            return 1;
        });

        self::assertSame(Command::SUCCESS, $this->tester()->execute(['--dry-run' => true]));
    }

    public function testAnEmptyExportIsRetriedOnceAfterReconnecting(): void
    {
        $calls = 0;
        $this->exporter->method('export')->willReturnCallback(function (string $key, string $target) use (&$calls): int {
            if (++$calls === 1) {
                return 0;   // connexion coupée : le service avale l'erreur et renvoie []
            }
            file_put_contents($target, 'x');

            return 5;
        });
        $this->factory->expects(self::exactly(2))->method('reconnectAll');
        $this->sharePoint->expects(self::once())->method('upload');
        $this->mailer->expects(self::never())->method('notifyError');

        self::assertSame(Command::SUCCESS, $this->tester()->execute(['--only' => ['produits']]));
        self::assertSame(2, $calls);
    }

    public function testAnExceptionIsRetriedOnceThenReported(): void
    {
        $calls = 0;
        $this->exporter->method('export')->willReturnCallback(function (string $key, string $target) use (&$calls): int {
            if (++$calls === 1) {
                throw new \RuntimeException('Unexpected EOF from the server');
            }
            file_put_contents($target, 'x');

            return 4;
        });
        $this->sharePoint->expects(self::once())->method('upload');
        $this->mailer->expects(self::never())->method('notifyError');

        self::assertSame(Command::SUCCESS, $this->tester()->execute(['--only' => ['produits']]));
        self::assertSame(2, $calls);
    }
}
