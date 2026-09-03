<?php

namespace App\Command;

use App\Repository\SalesWebServiceRepository;
use App\Service\Webservice\SageX3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'soa:send-xml',
    description: 'Envoie les flux XML SOA en attente vers Sage X3.',
)]
final class SoaSendXmlCommand extends Command
{
    public function __construct(
        private SalesWebServiceRepository $wsRepo,
        private SageX3Client              $sageClient,
        private EntityManagerInterface    $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $pending = $this->wsRepo->findPendingSoa();

        if (empty($pending)) {
            $io->success('Aucun flux SOA en attente.');
            return Command::SUCCESS;
        }

        $io->note(sprintf('%d flux SOA à envoyer.', count($pending)));

        foreach ($pending as $ws) {
            $io->text(sprintf('Envoi WS id=%d (SOA request id=%s)...', $ws->getId(), $ws->getSoaRequestId() ?? '?'));

            try {
                $result = $this->sageClient->run('WSCRESIH', $ws->getParameter());

                if (isset($result->resultXml)) {
                    $ws->setResult($result->resultXml);
                    $resultXml = simplexml_load_string($result->resultXml);
                    if ($resultXml instanceof \SimpleXMLElement) {
                        $docId = (string) ($resultXml->GRP[1]?->FLD ?? '');
                        if ($docId !== '') {
                            $ws->setErpDocumentId($docId);
                            $io->text(sprintf('  → ERP Document ID : %s', $docId));
                        }
                    }
                }

                if (isset($result->messages)) {
                    $messages = array_map(fn($m) => $m->message, (array) $result->messages);
                    $ws->setMessage(implode("\n", $messages));
                }

                $ws->setExecuted(true);
                $io->success(sprintf('Flux id=%d envoyé.', $ws->getId()));

            } catch (\Throwable $e) {
                $ws->setMessage($e->getMessage());
                $io->error(sprintf('Erreur flux id=%d : %s', $ws->getId(), $e->getMessage()));
            }

            $ws->setUpdatedAt(new \DateTime());
            $this->em->flush();
        }

        return Command::SUCCESS;
    }
}
