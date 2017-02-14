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

        $apontamentosByUsuario = $this->findBy(array('codUsuario' => $codUsuario), array('id' => 'DESC'));
        if (count($apontamentosByUsuario) > 0) {
            $ultimoApontamentoByUsuario = $apontamentosByUsuario[0];
            $ultimoApontamentoByUsuario->setDataFimConferencia(new \DateTime());
        }

        $em->persist($apontamentoEn);
        $em->flush();

        return $apontamentoEn;
    }

}