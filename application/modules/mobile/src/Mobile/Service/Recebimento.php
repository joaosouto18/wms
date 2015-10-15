<?php

namespace Mobile\Service;

use Wms\Domain\Entity\Recebimento as RecebimentoEntity;

class Recebimento
{
    protected $em;

    public function __construct($em)
    {
        $this->em = $em;
    }

    public function listarRecebimentosNaoEnderecados($status = RecebimentoEntity::STATUS_FINALIZADO)
    {
        /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
        $recebimentoRepo = $this->em->getRepository('wms:Recebimento');
        return $recebimentoRepo->naoEnderecadosByStatus($status);
    }

} 