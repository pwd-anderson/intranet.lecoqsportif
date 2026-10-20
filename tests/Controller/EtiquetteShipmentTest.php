<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Service\Etiquette;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Impression Étiquettes Expéditions : la recherche par commande d'achat est remplacée par la recherche par shipment
 * (SHIPMENTD). Le service Etiquette est simulé : ce test ne touche pas à X3.
 */
final class EtiquetteShipmentTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot(); // sinon le service simulé est perdu dès la 2e requête
        $c = static::getContainer();

        $etiquette = $this->createMock(Etiquette::class);
        $etiquette->method('getLignesShipment')->willReturnCallback(
            static fn (string $n): array => $n === 'EXPLCSI26-00127'
                ? [(object) ['N_SHIPMENT' => 'EXPLCSI26-00127', 'ARTICLE' => '2620717_XXXL', 'DESIGNATION' => 'POLO', 'QUANTITE_A_LIVREE' => 61.0]]
                : []
        );
        $c->set(Etiquette::class, $etiquette);

        $em = $c->get(EntityManagerInterface::class);
        $conn = $em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $conn->executeStatement('DELETE FROM user');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        $u = (new User())->setEmail('etq' . random_int(1, 999999) . '@example.test')->setRoles(['ROLE_USER']);
        $em->persist($u);
        $em->flush();
        $this->client->loginUser($u, 'main');
    }

    private function post(string $url, array $body): void
    {
        $this->client->request('POST', $url, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($body));
    }

    public function testShipmentSearchReturnsTheLines(): void
    {
        $this->post('/fr/etiquette/expedition/shipment', ['numero' => ' EXPLCSI26-00127 ']);
        self::assertResponseIsSuccessful();
        $rows = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('2620717_XXXL', $rows[0]['ARTICLE']);
        self::assertSame('EXPLCSI26-00127', $rows[0]['N_SHIPMENT']);
        self::assertEquals(61, $rows[0]['QUANTITE_A_LIVREE']);
    }

    public function testShipmentSearchRejectsAnEmptyNumber(): void
    {
        $this->post('/fr/etiquette/expedition/shipment', ['numero' => '  ']);
        self::assertResponseStatusCodeSame(400);
    }

    public function testUnknownShipmentGivesNoLine(): void
    {
        $this->post('/fr/etiquette/expedition/shipment', ['numero' => 'INCONNU']);
        self::assertResponseIsSuccessful();
        self::assertSame([], json_decode((string) $this->client->getResponse()->getContent(), true));
    }

    public function testThePageOffersShipmentInsteadOfPurchaseOrder(): void
    {
        $this->client->request('GET', '/fr/etiquette/expedition');
        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('<option value="shipment">N° de shipment</option>', $html);
        self::assertStringContainsString('/etiquette/expedition/shipment', $html);
        self::assertStringNotContainsString("commande d'achat", $html);
    }

    public function testTheOldPurchaseOrderRouteIsGone(): void
    {
        $this->post('/fr/etiquette/expedition/commande-achat', ['numero' => 'CAB25-03610/015']);
        self::assertResponseStatusCodeSame(404);
    }
}
