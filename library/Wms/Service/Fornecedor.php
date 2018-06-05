<?php

namespace Wms\Service;

use Doctrine\ORM\EntityManager;

class Fornecedor extends AbstractService
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
        $this->entity = 'wms:Pessoa\Papel\Fornecedor';
    }

}