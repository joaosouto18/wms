<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\Util\Sigla;

class CargaRepository extends EntityRepository
{

    public function save($carga)
    {

        $em = $this->getEntityManager();

        $em->beginTransaction();
        try {
            $tipoCarga = $em->getRepository('wms:Util\Sigla')->findOneBy(array('tipo' => 69,'referencia'=> $carga['codTipoCarga']));

            $enCarga = new Carga;
            $enCarga->setPlacaExpedicao($carga['placaExpedicao']);
            $enCarga->setCentralEntrega($carga['centralEntrega']);
            $enCarga->setCodCargaExterno($carga['codCargaExterno']);
            $enCarga->setExpedicao($carga['idExpedicao']);
            $enCarga->setPlacaCarga($carga['placaCarga']);
            $enCarga->setTipoCarga($tipoCarga);

            $em->persist($enCarga);
            $em->flush();
            $em->commit();

        } catch(\Exception $e) {
            $em->rollback();
            throw new \Exception();
        }

        return $enCarga;
    }

    /**
     * @param int $idCargaExterno
     * @param Sigla $siglaTipoCarga
     */
    public function cancelar($idCargaExterno, Sigla $siglaTipoCarga)
    {
        $cargaEntity = $this->findOneBy(array('codCargaExterno'=>$idCargaExterno,'tipoCarga'=>$siglaTipoCarga->getId()));
        $idCarga = $cargaEntity->getId();
        $pedidos = $this->getPedidos($idCarga);

        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $PedidoRepo */
        $PedidoRepo = $this->_em->getRepository('wms:Expedicao\Pedido');

        foreach($pedidos as $pedido) {
            $PedidoRepo->cancelar($pedido->getId());
        }

    }

    public function getPedidos($idCarga)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from('wms:Expedicao\Pedido', 'p')
            ->innerJoin('p.carga', 'c')
            ->where('p.carga = :IdCarga')
            ->setParameter('IdCarga', $idCarga);

        return $queryBuilder->getQuery()->getResult();
    }
}