<?php

namespace App\Tests\Controller\Module;

use App\Entity\Oc\Offer;
use App\Entity\User;
use App\Service\Oc\OcStatus;
use App\Service\Oc\OcX3Lookup;
use App\Service\Oc\OfferPdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test fonctionnel du module OC sur la base locale de test (intranet_lcs_test) : vrais contrôleur, voter, service
 * et Doctrine ; seul X3 est simulé. Les tables sont vidées avant chaque test.
 */
final class OcControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Sans cela, le client redémarre l'application entre deux requêtes et perd le X3 simulé ci-dessous : les
        // requêtes suivantes interrogeraient le VRAI X3 (c'était le cas avant le 2026-10-09).
        $this->client->disableReboot();
        $c = static::getContainer();

        $x3 = $this->createStub(OcX3Lookup::class);
        $x3->method('getCustomer')->willReturn(['CODE_CLIENT' => '20548', 'NOM_CLIENT' => 'CLIENT SIMULE', 'DEVISE' => 'EUR', 'CODE_TARIF' => 'TARIFSIM']);
        $x3->method('getCustomerDiscount')->willReturn(['REMISE' => 10, 'DATE_DEBUT' => '2027-01-01', 'DATE_FIN' => '2050-12-31']);
        $x3->method('getArticlesPrices')->willReturnCallback(static function (string $customer, array $codes): array {
            $known = [
                '2210037' => ['ARTICLE' => '2210037', 'DESIGNATION' => 'GANTS SIMULES', 'MODELE' => 'MODSIM', 'COLLECTION' => '2099-01-SS', 'CODE_TARIF' => 'TARIFSIM', 'DEVISE' => 'EUR', 'TARIF' => 11.1, 'MSRP' => 22.2],
                '1511960' => ['ARTICLE' => '1511960', 'DESIGNATION' => 'POLO SIMULE', 'MODELE' => 'MODSIM2', 'COLLECTION' => '2099-02-FW', 'CODE_TARIF' => 'TARIFSIM', 'DEVISE' => 'EUR', 'TARIF' => 33.3, 'MSRP' => 66.6],
            ];

            return array_intersect_key($known, array_flip($codes));
        });
        $c->set(OcX3Lookup::class, $x3);

        $pdf = $this->createStub(OfferPdfGenerator::class);
        $pdf->method('generate')->willReturn('%PDF-simule');
        $pdf->method('filename')->willReturn('OC_simule.pdf');
        $c->set(OfferPdfGenerator::class, $pdf);

        $this->em = $c->get(EntityManagerInterface::class);
        $conn = $this->em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['oc_offer_history', 'oc_offer_line', 'oc_offer', 'user'] as $t) {
            $conn->executeStatement("DELETE FROM $t");
        }
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function user(string $email, array $roles): User
    {
        $u = (new User())->setEmail($email)->setRoles($roles)->setFirstname(ucfirst(strstr($email, '@', true)))->setLastname('Test');
        $this->em->persist($u);
        $this->em->flush();

        return $u;
    }

    private function csrf(): string
    {
        $this->client->request('GET', '/fr/oc/new');
        self::assertResponseIsSuccessful();
        self::assertSame(1, preg_match("/const CSRF = '([^']+)'/", (string) $this->client->getResponse()->getContent(), $m), 'jeton CSRF dans la page');

        return $m[1];
    }

    /** @return array<string,mixed> */
    private function payload(array $override = []): array
    {
        return array_replace([
            'customerCode' => '20548', 'startDate' => '2026-11-01', 'endDate' => '2027-10-31', 'comment' => 'Test',
            'lines' => [['articleCode' => '2210037', 'tariff' => '30', 'msrp' => '60', 'vatRate' => 20, 'minQty' => 0, 'maxQty' => 500, 'comment' => 'ok']],
        ], $override);
    }

    private function post(string $url, array $body, ?string $csrf): array
    {
        $this->client->request('POST', $url, [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => (string) $csrf], json_encode($body));

        return json_decode((string) $this->client->getResponse()->getContent(), true) ?? [];
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $this->client->request('GET', '/fr/oc');
        self::assertResponseRedirects();
    }

    public function testNonSalesRoleIsForbidden(): void
    {
        $this->client->loginUser($this->user('compta@example.test', ['ROLE_USER', 'ROLE_ACCOUNTING']), 'main');
        $this->client->request('GET', '/fr/oc');
        self::assertResponseStatusCodeSame(403);
    }

    public function testSaveWithoutCsrfIsRejected(): void
    {
        $this->client->loginUser($this->user('alice@example.test', ['ROLE_USER', 'ROLE_SALES']), 'main');
        $res = $this->post('/fr/oc/save', $this->payload(), 'jeton-bidon');

        self::assertResponseStatusCodeSame(419);
        self::assertFalse($res['ok']);
        self::assertSame(0, $this->em->getRepository(Offer::class)->count([]));
    }

    public function testFullLifecycleCreateEditSubmit(): void
    {
        $alice = $this->user('alice@example.test', ['ROLE_USER', 'ROLE_SALES']);
        $this->client->loginUser($alice, 'main');
        $csrf = $this->csrf();

        // Création
        $res = $this->post('/fr/oc/save', $this->payload(), $csrf);
        self::assertResponseIsSuccessful();
        self::assertTrue($res['ok'], json_encode($res));
        self::assertSame('draft', $res['status']);
        self::assertCount(1, $res['warnings'], 'remise valable à partir de 2027-01-01, OC débutant le 2026-11-01');
        self::assertMatchesRegularExpression('/^OC\d{6}20548\d{8}$/', $res['number']);

        $this->em->clear();
        $offer = $this->em->getRepository(Offer::class)->find($res['id']);
        self::assertSame('CLIENT SIMULE', $offer->getCustomerName());
        self::assertSame('TARIFSIM', $offer->getCustomerTariffCode());
        self::assertSame('10.0000', $offer->getCustomerDiscount());
        self::assertSame('11.1000', $offer->getLines()[0]->getX3Tariff());
        self::assertSame('GANTS SIMULES', $offer->getLines()[0]->getArticleLabel());
        self::assertSame('30.0000', $offer->getLines()[0]->getTariff());
        self::assertSame('40.0000', $offer->getLines()[0]->getMargin());
        self::assertSame('33.3300', $offer->getLines()[0]->getInjectedPrice(), '30 / (1 - 10 %) = 33,33');
        self::assertSame('Alice Test', $offer->getCreatedByName());
        self::assertCount(1, $offer->getHistory());

        // Le brouillon se rouvre en modification
        $this->client->request('GET', "/fr/oc/{$res['id']}/edit");
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('2210037', (string) $this->client->getResponse()->getContent());

        // Modification : une 2e ligne
        $res2 = $this->post('/fr/oc/save', $this->payload(['id' => $res['id'], 'lines' => [
            ['articleCode' => '2210037', 'tariff' => '28', 'minQty' => 0, 'maxQty' => 500],
            ['articleCode' => '1511960', 'tariff' => '50', 'minQty' => 1, 'maxQty' => 10],
        ]]), $csrf);
        self::assertTrue($res2['ok'], json_encode($res2));
        self::assertSame($res['number'], $res2['number'], 'le numéro ne change pas');
        $this->em->clear();
        $offer = $this->em->getRepository(Offer::class)->find($res['id']);
        self::assertCount(2, $offer->getLines());
        self::assertCount(2, $offer->getHistory());

        // Soumission
        $sub = $this->post("/fr/oc/{$res['id']}/submit", [], $csrf);
        self::assertTrue($sub['ok'], json_encode($sub));
        self::assertSame('submitted', $sub['status']);

        // Plus modifiable, plus re-soumettable
        $this->client->request('GET', "/fr/oc/{$res['id']}/edit");
        self::assertResponseStatusCodeSame(403);
        $this->post("/fr/oc/{$res['id']}/submit", [], $csrf);
        self::assertResponseStatusCodeSame(403);
        $this->post('/fr/oc/save', $this->payload(['id' => $res['id']]), $csrf);
        self::assertResponseStatusCodeSame(403);

        // Page de détail
        $this->client->request('GET', "/fr/oc/{$res['id']}");
        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString($res['number'], $html);
        self::assertStringNotContainsString('Envoyé à X3', $html, 'le prix envoyé à X3 n\'est plus affiché (calculé et enregistré côté serveur)');
        self::assertStringContainsString("ne s'applique dans X3", html_entity_decode($html, ENT_QUOTES), 'avertissement de date affiché au détail');
    }

    public function testInvalidPayloadIsRefusedAndNothingIsSaved(): void
    {
        $this->client->loginUser($this->user('alice@example.test', ['ROLE_USER', 'ROLE_SALES']), 'main');
        $csrf = $this->csrf();

        $res = $this->post('/fr/oc/save', $this->payload(['lines' => [['articleCode' => '2210037', 'tariff' => '']]]), $csrf);
        self::assertResponseStatusCodeSame(422);
        self::assertFalse($res['ok']);
        self::assertStringContainsString('tarif OC', implode(' ', $res['errors']));

        $res = $this->post('/fr/oc/save', $this->payload(['lines' => [['articleCode' => 'INCONNU', 'tariff' => '10']]]), $csrf);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('INCONNU', implode(' ', $res['errors']));

        self::assertSame(0, $this->em->getRepository(Offer::class)->count([]));
    }

    public function testOtherSalesCannotSeeButManagementCan(): void
    {
        $alice = $this->user('alice@example.test', ['ROLE_USER', 'ROLE_SALES']);
        $this->client->loginUser($alice, 'main');
        $id = $this->post('/fr/oc/save', $this->payload(), $this->csrf())['id'];

        // Autre commercial : 403 sur le détail et sur la modification
        $this->client->loginUser($this->user('bob@example.test', ['ROLE_USER', 'ROLE_SALES']), 'main');
        $this->client->request('GET', "/fr/oc/$id");
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', "/fr/oc/$id/edit");
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/fr/oc');
        self::assertStringNotContainsString('CLIENT SIMULE', (string) $this->client->getResponse()->getContent());

        // Management : voit le détail et la liste, mais ne modifie pas le brouillon d'un autre
        $this->client->loginUser($this->user('boss@example.test', ['ROLE_USER', 'ROLE_MANAGEMENT']), 'main');
        $this->client->request('GET', "/fr/oc/$id");
        self::assertResponseIsSuccessful();
        $this->client->request('GET', '/fr/oc');
        self::assertStringContainsString('CLIENT SIMULE', (string) $this->client->getResponse()->getContent());
        $this->client->request('GET', "/fr/oc/$id/edit");
        self::assertResponseStatusCodeSame(403);
    }

    public function testCancelAndDuplicateRules(): void
    {
        $this->client->loginUser($this->user('alice@example.test', ['ROLE_USER', 'ROLE_SALES']), 'main');
        $csrf = $this->csrf();
        $id = $this->post('/fr/oc/save', $this->payload(), $csrf)['id'];

        // On ne repart d'une OC existante que si elle est REJETÉE
        $this->client->request('GET', "/fr/oc/$id/duplicate");
        self::assertResponseStatusCodeSame(404);

        // Annulation avec motif
        $res = $this->post("/fr/oc/$id/cancel", ['reason' => 'Client perdu'], $csrf);
        self::assertTrue($res['ok']);
        $this->em->clear();
        $offer = $this->em->getRepository(Offer::class)->find($id);
        self::assertSame(OcStatus::CANCELLED, $offer->getStatus());
        self::assertSame('Client perdu', $offer->getHistory()->last()->getReason());

        // Une OC rejetée peut servir de base à une nouvelle OC liée à elle
        $offer->setStatus(OcStatus::REJECTED);
        $this->em->flush();
        $this->client->request('GET', "/fr/oc/$id/duplicate");
        self::assertResponseIsSuccessful();
        $new = $this->post('/fr/oc/save', $this->payload(['sourceOfferId' => $id]), $csrf);
        self::assertTrue($new['ok'], json_encode($new));
        $this->em->clear();
        $created = $this->em->getRepository(Offer::class)->find($new['id']);
        self::assertSame($id, $created->getSourceOffer()->getId());
        self::assertNotSame($offer->getNumber(), $created->getNumber());
    }

    public function testAValidatedOfferCannotBeCancelledNotEvenByTheDirection(): void
    {
        [$id] = $this->submittedOfferByAlice();
        $this->client->loginUser($this->user('boss@example.test', ['ROLE_USER', 'ROLE_MANAGEMENT']), 'main');
        $csrf = $this->csrf();
        self::assertTrue($this->post("/fr/oc/$id/validate", [], $csrf)['ok']);

        $this->client->request('GET', "/fr/oc/$id");
        self::assertStringNotContainsString('id="btnCancelOffer"', (string) $this->client->getResponse()->getContent());
        $this->post("/fr/oc/$id/cancel", ['reason' => 'x'], $csrf);
        self::assertResponseStatusCodeSame(403);
        self::assertSame(OcStatus::VALIDATED, $this->statusOf($id));
    }

    public function testValidationPreparesTheX3FlowWithoutSendingAnything(): void
    {
        [$id] = $this->submittedOfferByAlice();
        $this->client->loginUser($this->user('boss@example.test', ['ROLE_USER', 'ROLE_MANAGEMENT']), 'main');
        $before = $this->em->getRepository(\App\Entity\SalesWebService::class)->count([]);

        self::assertTrue($this->post("/fr/oc/$id/validate", [], $this->csrf())['ok']);

        $this->em->clear();
        $offer = $this->em->getRepository(Offer::class)->find($id);
        self::assertCount(1, $offer->getFlows());
        self::assertSame('prepared', $offer->getFlows()->first()->getStatus());
        self::assertStringContainsString('WITMREF', $offer->getFlows()->first()->getXml());
        self::assertSame(OcStatus::VALIDATED, $offer->getStatus(), 'envoi désactivé : l\'OC reste « validée »');
        self::assertSame($before, $this->em->getRepository(\App\Entity\SalesWebService::class)->count([]), 'rien dans sales_web_service');

        $this->client->request('GET', "/fr/oc/$id");
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Envoi à X3 désactivé', $html);
        self::assertStringContainsString('WSCRESPL', $html);

        // Le commercial ne voit pas les flux techniques.
        $this->client->loginUser($this->em->getRepository(User::class)->findOneBy(['email' => 'alice@example.test']), 'main');
        $this->client->request('GET', "/fr/oc/$id");
        self::assertStringNotContainsString('WSCRESPL', (string) $this->client->getResponse()->getContent());
    }

    public function testValidationMenuListsOnlySubmittedOffersAndIsReservedToTheDirection(): void
    {
        [$submittedId, $csrf] = $this->submittedOfferByAlice();
        $draftId = $this->post('/fr/oc/save', $this->payload(), $csrf)['id'];

        $this->client->request('GET', '/fr/oc/validation');
        self::assertResponseStatusCodeSame(403, 'un commercial n\'accède pas à la validation');

        $this->client->loginUser($this->user('boss@example.test', ['ROLE_USER', 'ROLE_MANAGEMENT']), 'main');
        $this->client->request('GET', '/fr/oc/validation');
        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Validation OC', $html);
        self::assertStringContainsString('"id":' . $submittedId, $html);
        self::assertStringNotContainsString('"id":' . $draftId . ',', $html, 'les brouillons ne sont pas à valider');
        // Menu repliable à trois entrées
        foreach (['Nouvelle OC', 'Liste OC', 'Validation OC'] as $label) {
            self::assertStringContainsString($label, $html);
        }
    }

    public function testListOffersModifyOnlyForDraftsAndNothingElse(): void
    {
        [$submittedId, $csrf] = $this->submittedOfferByAlice();
        $draftId = $this->post('/fr/oc/save', $this->payload(), $csrf)['id'];

        $this->client->request('GET', '/fr/oc');
        $html = html_entity_decode((string) $this->client->getResponse()->getContent(), ENT_QUOTES);
        preg_match_all('/"id":(\d+)[^{}]*?"editUrl":(null|"[^"]*")/', $html, $m, PREG_SET_ORDER);
        $edit = [];
        foreach ($m as $row) { $edit[(int) $row[1]] = $row[2]; }
        self::assertNotSame('null', $edit[$draftId] ?? 'null', 'brouillon : bouton Modifier');
        self::assertSame('null', $edit[$submittedId] ?? 'absent', 'OC soumise : consultation seule');
    }

    public function testListPageNoLongerHasANewOfferButton(): void
    {
        $this->client->loginUser($this->user('alice@example.test', ['ROLE_USER', 'ROLE_SALES']), 'main');
        $this->client->request('GET', '/fr/oc');
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('class="btn btn-sm btn-primary">Nouvelle OC', (string) $this->client->getResponse()->getContent());
        self::assertStringNotContainsString('Validation OC', (string) $this->client->getResponse()->getContent(), 'entrée de menu réservée à la Direction');
    }

    // ── Décision de la Direction ─────────────────────────────────────────────

    /** Crée et soumet une OC en tant qu'Alice ; renvoie [id, jeton CSRF d'Alice]. */
    private function submittedOfferByAlice(): array
    {
        $this->client->loginUser($this->user('alice@example.test', ['ROLE_USER', 'ROLE_SALES']), 'main');
        $csrf = $this->csrf();
        $id = $this->post('/fr/oc/save', $this->payload(), $csrf)['id'];
        self::assertTrue($this->post("/fr/oc/$id/submit", [], $csrf)['ok']);

        return [$id, $csrf];
    }

    private function statusOf(int $id): OcStatus
    {
        $this->em->clear();

        return $this->em->getRepository(Offer::class)->find($id)->getStatus();
    }

    public function testDirectionValidatesASubmittedOffer(): void
    {
        [$id] = $this->submittedOfferByAlice();

        $this->client->loginUser($this->user('boss@example.test', ['ROLE_USER', 'ROLE_MANAGEMENT']), 'main');
        // Depuis la liste classique : consultation seule, aucun bouton de décision.
        $this->client->request('GET', "/fr/oc/$id");
        $listHtml = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('id="btnValidateOffer"', $listHtml);
        self::assertStringNotContainsString('id="btnRejectOffer"', $listHtml);

        // Depuis le menu « Validation OC » : boutons de décision.
        $this->client->request('GET', "/fr/oc/$id?from=validation");
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('id="btnValidateOffer"', $html, 'bouton de décision visible pour la Direction');
        self::assertStringContainsString('id="btnRejectOffer"', $html);
        self::assertStringContainsString('attend votre décision', $html);

        $res = $this->post("/fr/oc/$id/validate", [], $this->csrf());
        self::assertTrue($res['ok'], json_encode($res));
        self::assertSame('validated', $res['status']);

        $this->em->clear();
        $offer = $this->em->getRepository(Offer::class)->find($id);
        self::assertSame('Boss Test', $offer->getHistory()->last()->getUserName());
        self::assertNotNull($offer->getDecidedAt());
    }

    public function testRejectionNeedsAReasonAndIsFinal(): void
    {
        [$id] = $this->submittedOfferByAlice();
        $this->client->loginUser($this->user('boss@example.test', ['ROLE_USER', 'ROLE_ADMIN']), 'main');
        $csrf = $this->csrf();

        $res = $this->post("/fr/oc/$id/reject", ['reason' => '  '], $csrf);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('motif', implode(' ', $res['errors']));
        self::assertSame(OcStatus::SUBMITTED, $this->statusOf($id));

        $res = $this->post("/fr/oc/$id/reject", ['reason' => 'Marge insuffisante'], $csrf);
        self::assertTrue($res['ok'], json_encode($res));
        self::assertSame(OcStatus::REJECTED, $this->statusOf($id));

        // Un rejet est définitif : ni validation ni nouvelle décision
        $this->post("/fr/oc/$id/validate", [], $csrf);
        self::assertResponseStatusCodeSame(403);

        $this->em->clear();
        self::assertSame('Marge insuffisante', $this->em->getRepository(Offer::class)->find($id)->getHistory()->last()->getReason());
    }

    public function testOnlyTheDirectionCanDecide(): void
    {
        [$id, $aliceCsrf] = $this->submittedOfferByAlice();

        // Le propriétaire ne valide pas sa propre OC
        $this->post("/fr/oc/$id/validate", [], $aliceCsrf);
        self::assertResponseStatusCodeSame(403);
        $this->post("/fr/oc/$id/reject", ['reason' => 'je me rejette'], $aliceCsrf);
        self::assertResponseStatusCodeSame(403);

        // Ni un autre commercial, ni le contrôle de gestion (consultation seulement)
        foreach ([['bob@example.test', 'ROLE_SALES'], ['ctrl@example.test', 'ROLE_CONTROLLING']] as [$email, $role]) {
            $this->client->loginUser($this->user($email, ['ROLE_USER', $role]), 'main');
            $csrf = $this->csrf();
            $this->post("/fr/oc/$id/validate", [], $csrf);
            self::assertResponseStatusCodeSame(403, $email);
            $this->client->request('GET', "/fr/oc/$id");
            if ($role === 'ROLE_CONTROLLING') {
                // Le contrôle de gestion consulte l'OC mais n'a aucun bouton de décision (le script de la page cite
                // toujours l'identifiant du bouton : on teste l'attribut HTML exact).
                self::assertResponseIsSuccessful();
                self::assertStringNotContainsString('id="btnValidateOffer"', (string) $this->client->getResponse()->getContent());
            } else {
                self::assertResponseStatusCodeSame(403, 'un autre commercial ne voit pas l\'OC');
            }
        }

        self::assertSame(OcStatus::SUBMITTED, $this->statusOf($id));
    }

    public function testValidationWithoutCsrfTokenIsRefused(): void
    {
        [$id] = $this->submittedOfferByAlice();
        $this->client->loginUser($this->user('boss@example.test', ['ROLE_USER', 'ROLE_MANAGEMENT']), 'main');

        $this->post("/fr/oc/$id/validate", [], 'jeton-bidon');

        self::assertResponseStatusCodeSame(419);
        self::assertSame(OcStatus::SUBMITTED, $this->statusOf($id));
    }

    public function testValidationIsRefusedWhenTheCustomerDiscountChangedSinceSaving(): void
    {
        [$id] = $this->submittedOfferByAlice();
        // X3 simulé = remise 10 % ; on fait croire que l'OC a été enregistrée avec 15 %
        $this->em->getConnection()->executeStatement('UPDATE oc_offer SET customer_discount = 15 WHERE id = ?', [$id]);

        $this->client->loginUser($this->user('boss@example.test', ['ROLE_USER', 'ROLE_MANAGEMENT']), 'main');
        $res = $this->post("/fr/oc/$id/validate", [], $this->csrf());

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('remise client a changé', implode(' ', $res['errors']));
        self::assertSame(OcStatus::SUBMITTED, $this->statusOf($id));
    }

    public function testPdfIsOnlyAvailableOnceValidatedAndToAuthorizedUsers(): void
    {
        [$id, $csrf] = $this->submittedOfferByAlice();

        $this->client->request('GET', "/fr/oc/$id/pdf");
        self::assertSame(403, $this->client->getResponse()->getStatusCode(), 'pas de PDF avant validation');

        $this->client->loginUser($this->user('boss@example.test', ['ROLE_USER', 'ROLE_MANAGEMENT']), 'main');
        self::assertTrue($this->post("/fr/oc/$id/validate", [], $this->csrf())['ok']);

        $this->client->request('GET', "/fr/oc/$id");
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Télécharger le PDF', $html);
        self::assertStringContainsString('id="btnResendPdf"', $html);

        $this->client->request('GET', "/fr/oc/$id/pdf");
        $res = $this->client->getResponse();
        self::assertSame(200, $res->getStatusCode());
        self::assertSame('application/pdf', $res->headers->get('Content-Type'));
        self::assertStringContainsString('OC_simule.pdf', (string) $res->headers->get('Content-Disposition'));
        self::assertSame('%PDF-simule', $res->getContent());

        // Le créateur télécharge mais ne renvoie pas ; un autre commercial n'a accès à rien.
        $this->client->loginUser($this->em->getRepository(User::class)->findOneBy(['email' => 'alice@example.test']), 'main');
        $this->client->request('GET', "/fr/oc/$id/pdf");
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->client->request('GET', "/fr/oc/$id");
        self::assertStringNotContainsString('id="btnResendPdf"', (string) $this->client->getResponse()->getContent());
        self::assertSame(403, $this->postStatus("/fr/oc/$id/resend-pdf", $this->csrf()));

        $this->client->loginUser($this->user('bob@example.test', ['ROLE_USER', 'ROLE_SALES']), 'main');
        $this->client->request('GET', "/fr/oc/$id/pdf");
        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testResendIsRefusedWithoutCsrfAndDoesNotFailInTestEnvironment(): void
    {
        [$id] = $this->submittedOfferByAlice();
        $this->client->loginUser($this->user('boss@example.test', ['ROLE_USER', 'ROLE_MANAGEMENT']), 'main');
        self::assertTrue($this->post("/fr/oc/$id/validate", [], $this->csrf())['ok']);

        self::assertSame(419, $this->postStatus("/fr/oc/$id/resend-pdf", null));
        // En environnement de test les mails sont désactivés : le renvoi répond « échec » proprement (jamais d'erreur 500).
        self::assertSame(502, $this->postStatus("/fr/oc/$id/resend-pdf", $this->csrf()));
    }

    private function postStatus(string $url, ?string $csrf): int
    {
        $this->client->request('POST', $url, [], [], array_filter(['CONTENT_TYPE' => 'application/json', 'HTTP_X-CSRF-Token' => $csrf]), '{}');

        return $this->client->getResponse()->getStatusCode();
    }
}
