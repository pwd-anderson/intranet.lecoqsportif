<?php

namespace App\Service\Webservice;

use App\Entity\SalesWebService;
use Doctrine\ORM\EntityManagerInterface;

final class WebServiceDispatcher
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function dispatch(string $webserviceName, string $xml, ?int $ocOfferId = null): SalesWebService
    {
        $ws = new SalesWebService();
        $ws->setName($webserviceName);
        $ws->setParameter($xml);
        $ws->setOcOfferId($ocOfferId);

        $this->em->persist($ws);
        $this->em->flush();

        return $ws;
    }
}
