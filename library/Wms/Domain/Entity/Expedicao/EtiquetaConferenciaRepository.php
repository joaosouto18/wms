<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao\EtiquetaConferencia;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class EtiquetaConferenciaRepository extends EntityRepository
{

    /**
     * @param array $dadosEtiqueta
     * @param int $status
     * @return int
     * @throws \Exception
     */
    public function save(array $dadosEtiqueta, $statusEntity)
    {
        $enEtiquetaSeparacao = new EtiquetaConferencia();
        $enEtiquetaSeparacao->setStatus($statusEntity);
        $enEtiquetaSeparacao->setExpedicao($dadosEtiqueta['expedicao']);

        \Zend\Stdlib\Configurator::configure($enEtiquetaSeparacao, $dadosEtiqueta);

        $this->_em->persist($enEtiquetaSeparacao);

        return $enEtiquetaSeparacao->getId();
    }


    public function getEtiquetasByStatus($statusEtiqueta,$idExpedicao){

        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('e.codStatus,e.id')
            ->from('wms:Expedicao\EtiquetaConferencia','e')

            ->where('e.codExpedicao = :idExpedicao')
            ->andwhere('e.codStatus = :status')
            ->setParameter('idExpedicao', $idExpedicao)
            ->setParameter('status', $statusEtiqueta);

        return $dql->getQuery()->getArrayResult();
    }

    public function getEtiquetaByCodBarras($idExpedicao,$etiqueta){
        $sql= $this->getEntityManager()->createQueryBuilder()
            ->select(
                'es.codStatus'
            )
            ->from('wms:Expedicao\EtiquetaConferencia','es')

            ->where('es.codEtiquetaSeparacao = :codBarras')
            ->andWhere('es.codExpedicao = :idExpedicao')
            ->setParameter('codBarras', $etiqueta)
            ->setParameter('idExpedicao', $idExpedicao);
        $resultado=$sql->getQuery()->getArrayResult();

        return $resultado;

    }


}