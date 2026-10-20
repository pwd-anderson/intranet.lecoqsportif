<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Module Suivi Sellin global. Il présentera des remises et des avoirs par client :
 * accès réservé par rôle côté serveur, pas seulement par le menu. Ce test ne lit aucune donnée : la route de données est seulement vérifiée en accès refusé.
 */
final class SuiviSellinAccessTest extends WebTestCase
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

    private function login(array $roles): User
    {
        $u = (new User())->setEmail('u' . random_int(1, 999999) . '@example.test')->setRoles($roles);
        $this->em->persist($u);
        $this->em->flush();
        $this->client->loginUser($u, 'main');

        return $u;
    }

    /** @return iterable<string, array{list<string>}> */
    public static function allowedRoles(): iterable
    {
        yield 'ventes' => [['ROLE_USER', 'ROLE_SALES']];
        yield 'ADV' => [['ROLE_USER', 'ROLE_ADV']];
        yield 'management' => [['ROLE_USER', 'ROLE_MANAGEMENT']];
        yield 'controlling' => [['ROLE_USER', 'ROLE_CONTROLLING']];
        yield 'admin' => [['ROLE_USER', 'ROLE_ADMIN']];
    }

    #[DataProvider('allowedRoles')]
    public function testAuthorizedRolesGetThePageWithEveryTabAndItsQueries(array $roles): void
    {
        $this->login($roles);

        $this->client->request('GET', '/fr/suivi-sellin');
        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertSame(13, substr_count($html, 'role="tab"'), 'un onglet par menu du template');
        // Les requêtes de chaque onglet sont embarquées pour le bouton « Requêtes » du bas de page.
        self::assertStringContainsString('LCS_X3SALESX', $html);
        self::assertStringContainsString('CREDITMEMO', $html);
        self::assertStringContainsString('LCS_SELLOUT_SALES', $html);
    }

    /** @return iterable<string, array{list<string>}> */
    public static function forbiddenRoles(): iterable
    {
        yield 'achats' => [['ROLE_USER', 'ROLE_PURCHASING']];
        yield 'compta' => [['ROLE_USER', 'ROLE_ACCOUNTING']];
        yield 'sans rôle métier' => [['ROLE_USER']];
    }

    #[DataProvider('forbiddenRoles')]
    public function testOtherRolesGetForbiddenEvenByTypingTheUrl(array $roles): void
    {
        $this->login($roles);
        foreach (['/fr/suivi-sellin', '/fr/suivi-sellin/data/data'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $this->client->request('GET', '/fr/suivi-sellin');
        self::assertResponseRedirects();
    }

    public function testNotesAreSharedAndOnlyTheirAuthorCanDeleteThem(): void
    {
        $this->em->getConnection()->executeStatement('DELETE FROM suivi_sellin_note');

        // Le jeton CSRF vient de la page de l'application, comme pour le navigateur.
        $author = $this->login(['ROLE_USER', 'ROLE_SALES']);
        $this->client->request('GET', '/fr/suivi-sellin');
        preg_match('/"csrf":"([^"]+)"/', (string) $this->client->getResponse()->getContent(), $m);
        $csrf = $m[1] ?? '';
        self::assertNotSame('', $csrf);

        $headers = ['HTTP_X-CSRF-Token' => $csrf, 'CONTENT_TYPE' => 'application/json'];

        // Sans jeton : refusé.
        $this->client->request('POST', '/fr/suivi-sellin/notes', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['c' => '10012', 'text' => 'x']));
        self::assertResponseStatusCodeSame(419);

        $this->client->request('POST', '/fr/suivi-sellin/notes', [], [], $headers, json_encode(['c' => '10012', 'text' => 'RDV le 12/11']));
        self::assertResponseStatusCodeSame(201);
        $id = json_decode((string) $this->client->getResponse()->getContent(), true)['id'];

        // Note vide : refusée.
        $this->client->request('POST', '/fr/suivi-sellin/notes', [], [], $headers, json_encode(['c' => '10012', 'text' => '  ']));
        self::assertResponseStatusCodeSame(422);

        // Un collègue (autre compte) voit la note mais ne peut pas la supprimer.
        $this->login(['ROLE_USER', 'ROLE_ADV']);
        $this->client->request('GET', '/fr/suivi-sellin');
        preg_match('/"csrf":"([^"]+)"/', (string) $this->client->getResponse()->getContent(), $m2);
        $headers2 = ['HTTP_X-CSRF-Token' => $m2[1], 'CONTENT_TYPE' => 'application/json'];
        $this->client->request('GET', '/fr/suivi-sellin/notes');
        $notes = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertCount(1, $notes);
        self::assertSame('RDV le 12/11', $notes[0]['text']);
        self::assertSame('10012', $notes[0]['c']);

        $this->client->request('DELETE', '/fr/suivi-sellin/notes/' . $id, [], [], $headers2);
        self::assertResponseStatusCodeSame(403);

        // L'auteur peut la supprimer.
        $this->client->loginUser($author, 'main');
        $this->client->request('DELETE', '/fr/suivi-sellin/notes/' . $id, [], [], $headers);
        self::assertResponseIsSuccessful();
        $this->client->request('GET', '/fr/suivi-sellin/notes');
        self::assertSame([], json_decode((string) $this->client->getResponse()->getContent(), true));
    }
}
