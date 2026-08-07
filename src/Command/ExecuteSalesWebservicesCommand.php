<?php

namespace App\Command;

use App\Entity\SalesWebService;
use App\Service\Webservice\SageX3Client;
use Doctrine\ORM\EntityManagerInterface;
use SimpleXMLElement;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sales:webservices:execute',
    description: 'Exécute les webservices Sage X3 en attente (sales_web_service)',
)]
class ExecuteSalesWebservicesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private SageX3Client $sageClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repo        = $this->em->getRepository(SalesWebService::class);
        $webservices = $repo->findBy(['executed' => false]);

        if (empty($webservices)) {
            $output->writeln('<info>Aucun webservice en attente.</info>');
            return Command::SUCCESS;
        }

        foreach ($webservices as $webservice) {
            $output->writeln(sprintf(
                '<comment>- ID %d - %s</comment>',
                $webservice->getId(),
                $webservice->getName()
            ));

            try {
                $result = $this->sageClient->run($webservice->getName(), $webservice->getParameter());

                $webservice->setUpdatedAt(new \DateTime());

                if (isset($result->resultXml)) {
                    $webservice->setResult($result->resultXml);

                    $xml = simplexml_load_string($result->resultXml);
                    if ($xml instanceof SimpleXMLElement) {
                        $documentId = (string) ($xml->GRP[1]?->FLD ?? '');
                        if ($documentId !== '') {
                            $webservice->setErpDocumentId($documentId);
                            $webservice->setExecuted(true);
                        }
                    }
                }

                if (isset($result->messages)) {
                    $messages = array_map(fn($m) => $m->message, (array) $result->messages);
                    $webservice->setMessage(implode("\n", $messages));
                }

            } catch (\Throwable $e) {
                $output->writeln('<error>Erreur : ' . $e->getMessage() . '</error>');
                $webservice->setMessage('Erreur : ' . $e->getMessage());
                $webservice->setUpdatedAt(new \DateTime());
            }

            $this->em->persist($webservice);
        }

        $this->em->flush();
        $output->writeln('<info>Exécution terminée.</info>');

        return Command::SUCCESS;
    }
}
