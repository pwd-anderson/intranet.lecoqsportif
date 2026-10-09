<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Service\Sales;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Stat Contacts Clients (ADV) : emails et téléphones de clients, donc accès réservé par rôle côté serveur (pas seulement
 * par le menu). Le service Sales est simulé : ce test ne touche pas à X3. Base locale de test (intranet_lcs_test).
 */
final class ContactsClientsAccessTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Sans cela, le client redémarre l'application entre deux requêtes et perd le service simulé ci-dessous
        // (la 2e requête interrogerait alors le vrai X3).
        $this->client->disableReboot();
        $c = static::getContainer();

        $sales = $this->createStub(Sales::class);
        $sales->method('getContactsClients')->willReturn([
            (object) ['CODE_CLIENT' => '10004', 'NOM_CLIENT' => 'DOLCE SPORT', 'LIBELLE_FONCTION' => 'Email Confirmation de Commande', 'EMAIL' => 'achats@example.test'],
        ]);
        $c->set(Sales::class, $sales);

        $this->em = $c->get(EntityManagerInterface::class);
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
    public static function allowedRoles(): iterable
    {
        yield 'ADV' => [['ROLE_USER', 'ROLE_ADV']];
        yield 'management' => [['ROLE_USER', 'ROLE_MANAGEMENT']];
        yield 'controlling' => [['ROLE_USER', 'ROLE_CONTROLLING']];
        yield 'admin' => [['ROLE_USER', 'ROLE_ADMIN']];
    }

    #[DataProvider('allowedRoles')]
    public function testAuthorizedRolesCanOpenThePageAndTheData(array $roles): void
    {
        $this->login($roles);

        $this->client->request('GET', '/fr/sales/contacts_clients');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/fr/sales/contacts_clients_json');
        self::assertResponseIsSuccessful();
        $rows = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('achats@example.test', $rows[0]['EMAIL']);
    }

    /** @return iterable<string, array{list<string>}> */
    public static function forbiddenRoles(): iterable
    {
        yield 'ventes seulement' => [['ROLE_USER', 'ROLE_SALES']];
        yield 'achats' => [['ROLE_USER', 'ROLE_PURCHASING']];
        yield 'compta' => [['ROLE_USER', 'ROLE_ACCOUNTING']];
        yield 'sans rôle métier' => [['ROLE_USER']];
    }

    #[DataProvider('forbiddenRoles')]
    public function testOtherRolesCannotReadContactsEvenByTypingTheUrl(array $roles): void
    {
        $this->login($roles);

        $this->client->request('GET', '/fr/sales/contacts_clients');
        self::assertResponseStatusCodeSame(403);

        $this->client->request('GET', '/fr/sales/contacts_clients_json');
        self::assertResponseStatusCodeSame(403);
        self::assertStringNotContainsString('achats@example.test', (string) $this->client->getResponse()->getContent());
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $this->client->request('GET', '/fr/sales/contacts_clients_json');
        self::assertResponseRedirects();
    }
}
