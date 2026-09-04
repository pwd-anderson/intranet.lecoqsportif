<?php

namespace App\Command\Import;

use App\Entity\X3Collection;
use App\Factory\MssqlManagerFactory;
use App\Repository\X3CollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:x3-collection:refresh',
    description: 'Recopie la liste des collections X3 (SEI Cube) dans la table locale x3_collection (MySQL)',
)]
class RefreshX3CollectionCommand extends Command
{
    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private EntityManagerInterface $em,
        private X3CollectionRepository $repository,
        #[Autowire('%db.lcs_sei%')]
        private string $dbLcsSei,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $mssqlSei = $this->mssqlManagerFactory->create($this->dbLcsSei);

        $rows = $mssqlSei->executeQuery("
            SELECT DISTINCT
                C.SERIESCODE AS COLLECTION
            FROM SEI_X3_LCS.LCS_COLLECTION C
            WHERE C.SERIESCODE >= '2023-01-SS'
            ORDER BY C.SERIESCODE DESC
        ");

        if ($rows === []) {
            $io->error('Aucune collection récupérée depuis le SEI Cube — abandon (table locale non modifiée).');
            return Command::FAILURE;
        }

        $codes = array_map(static fn($row) => (string) $row->COLLECTION, $rows);
        $existing = $this->repository->findAll();
        $existingByCode = [];
        foreach ($existing as $entity) {
            $existingByCode[$entity->getSeriesCode()] = $entity;
        }

        $now = new \DateTimeImmutable();
        $created = 0;

        foreach ($codes as $code) {
            if (isset($existingByCode[$code])) {
                continue;
            }
            $collection = (new X3Collection())
                ->setSeriesCode($code)
                ->setUpdatedAt($now);
            $this->em->persist($collection);
            $created++;
        }

        // Supprime les collections locales qui n'existent plus côté X3
        $removed = 0;
        foreach ($existingByCode as $code => $entity) {
            if (!in_array($code, $codes, true)) {
                $this->em->remove($entity);
                $removed++;
            }
        }

        $this->em->flush();

        $io->success(sprintf(
            '%d collections synchronisées (%d ajoutées, %d supprimées, %d inchangées).',
            count($codes),
            $created,
            $removed,
            count($codes) - $created
        ));

        return Command::SUCCESS;
    }
}
