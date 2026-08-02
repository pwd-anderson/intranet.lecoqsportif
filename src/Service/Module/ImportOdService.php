<?php

namespace App\Service\Module;

use App\Entity\SalesWebService;
use App\Service\Webservice\WebServiceDispatcher;
use App\Service\Webservice\XmlBuilder;

final class ImportOdService
{
    public function __construct(
        private WebServiceDispatcher $dispatcher,
    ) {}

    public function generate(array $entete, array $lignes): SalesWebService
    {
        $xml = XmlBuilder::buildOD($entete, $lignes);
        return $this->dispatcher->dispatch('ZSAIOD', $xml);
    }
}
