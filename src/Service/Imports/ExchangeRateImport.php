<?php

namespace App\Service\Imports;

use App\Entity\ExchangeRatesMoyen;
use App\Service\Tools\GraphMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class ExchangeRateImport
{
    private const API_URL = 'https://api.exchangerate.host/live';

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer
    ) {}

    public function fetchAndStoreMonthlyAveragesFromXml(): void
    {
        $url = 'https://www.backend-rates.bazg.admin.ch/api/xmlavgmonth';

        try {
            $response = $this->httpClient->request('GET', $url);
            $xmlContent = $response->getContent();

            $xml = simplexml_load_string($xmlContent);
            $xml->registerXPathNamespace('ns', 'https://www.backend-rates.ezv.admin.ch/avgratesmonth');

            $monthNode = $xml->xpath('//ns:monat');
            $monthRaw = $monthNode[0] ?? null;
            if (!$monthRaw) {
                throw new \RuntimeException('Mois non trouvé dans le XML');
            }

            $dateCours = \DateTime::createFromFormat('Y-m', (string) $monthRaw)->format('Y-m-01');

            $targetCodes = ['eur', 'usd'];
            $devises = $xml->xpath('//ns:devise');

            foreach ($devises as $devise) {
                $attributes = $devise->attributes();
                $code = strtolower((string) $attributes['code']);

                if (!in_array($code, $targetCodes)) {
                    continue;
                }

                $sourceCurrency = strtoupper($code);
                $rate = (float) $devise->kurs;

                $this->insertOrUpdateRate($sourceCurrency, 'CHF', $rate, $dateCours);
            }

            // ➡️ Ajouter aussi CHF ➝ CHF = 1
            $this->insertOrUpdateRate('CHF', 'CHF', 1.0000, $dateCours);

        } catch (\Throwable $e) {
            $this->logger->error('Erreur XML ExchangeRate', ['exception' => $e]);
            $this->graphMailer->notifyError('❌ OGIER Erreur : Flux XML taux moyen', $e);
        }
    }

    public function insertOrUpdateRate(string $sourceCurrency, string $targetCurrency, float $rate, string $dateCours): void
    {
        $date = new \DateTime($dateCours);

        $repository = $this->em->getRepository(ExchangeRatesMoyen::class);

        $existing = $repository->findOneBy([
            'sourceCurrency' => $sourceCurrency,
            'targetCurrency' => $targetCurrency,
            'dateCours' => $date,
        ]);

        if ($existing) {
            $existing->setRate($rate);
            $existing->setInsertedAt(new \DateTimeImmutable());
        } else {
            $newRate = new ExchangeRatesMoyen();
            $newRate
                ->setSourceCurrency($sourceCurrency)
                ->setTargetCurrency($targetCurrency)
                ->setRate($rate)
                ->setDateCours($date)
                ->setInsertedAt(new \DateTimeImmutable());

            $this->em->persist($newRate);
        }

        // flush en dehors de la boucle si beaucoup de lignes, sinon OK ici
        $this->em->flush();
    }
}
