<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Bouton « Import » du plan transport prévision : relance la commande d'import, donc réservé côté serveur (rôles de la
 * stat) et protégé par jeton CSRF. Ces refus sont rendus avant tout lancement de la commande : rien n'est importé.
 */
final class PlanTransportImportAccessTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $conn = $this->em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $conn->executeStatement('DELETE FROM user');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function login(array $roles): void
    {
        $u = (new User())->setEmail('u' . random_int(1, 999999) . '@example.test')->setRoles($roles);
        $this->em->persist($u);
        $this->em->flush();
        $this->client->loginUser($u, 'main');
    }

    /** @return iterable<string, array{list<string>}> */
    public static function forbiddenRoles(): iterable
    {
        yield 'ventes' => [['ROLE_USER', 'ROLE_SALES']];
        yield 'adv' => [['ROLE_USER', 'ROLE_ADV']];
        yield 'sans rôle métier' => [['ROLE_USER']];
    }

    #[DataProvider('forbiddenRoles')]
    public function testOtherRolesAreRefused(array $roles): void
    {
        $this->login($roles);
        $this->client->request('POST', '/fr/achat/plan_transport_prevision_import', ['_token' => 'x']);

        self::assertResponseStatusCodeSame(403);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertFalse($data['success']);
    }

    public function testAnInvalidCsrfTokenIsRefusedEvenForPurchasing(): void
    {
        $this->login(['ROLE_USER', 'ROLE_PURCHASING']);
        $this->client->request('POST', '/fr/achat/plan_transport_prevision_import', ['_token' => 'faux']);

        self::assertResponseStatusCodeSame(403);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertStringContainsString('Session expirée', $data['message']);
    }

    public function testGetIsNotAllowed(): void
    {
        $this->login(['ROLE_USER', 'ROLE_PURCHASING']);
        $this->client->request('GET', '/fr/achat/plan_transport_prevision_import');

        self::assertResponseStatusCodeSame(405);
    }
}
