<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class ApontamentoMapaRepository extends EntityRepository
{

    public function save($mapaSeparacao,$codUsuario)
    {
        $em = $this->getEntityManager();
        $apontamentoEn = new ApontamentoMapa();
        $apontamentoEn->setDataConferencia(new \DateTime());
        $apontamentoEn->setCodMapaSeparacao($mapaSeparacao->getId());
        $apontamentoEn->setCodUsuario($codUsuario);
        $apontamentoEn->setMapaSeparacao($mapaSeparacao);

        $em->persist($apontamentoEn);
        $em->flush();

        return $apontamentoEn;
    }

}