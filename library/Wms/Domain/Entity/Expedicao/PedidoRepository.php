<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;

class PedidoRepository extends EntityRepository
{

    /**
     * @param $pedido
     * @return Pedido
     * @throws \Exception
     */
    public function save($pedido)
    {

        $em = $this->getEntityManager();

        $em->beginTransaction();
        try {
            $enPedido = new Pedido;

            $SiglaRepo      = $em->getRepository('wms:Util\Sigla');
            $entitySigla    = $SiglaRepo->findOneBy(array('sigla' => $pedido['tipoPedido']));

            if ($entitySigla == null) {
                throw new \Exception('O tipo de pedido '.$pedido['tipoPedido'].' não esta cadastrado');
            }

            $enPedido->setId($pedido['codPedido']);
            $enPedido->setTipoPedido($entitySigla);
            $enPedido->setLinhaEntrega($pedido['linhaEntrega']);
            $enPedido->setCentralEntrega($pedido['centralEntrega']);
            $enPedido->setCarga($pedido['carga']);
            $enPedido->setItinerario($pedido['itinerario']);
            $enPedido->setPessoa($pedido['pessoa']);
            $enPedido->setPontoTransbordo($pedido['pontoTransbordo']);
            $enPedido->setEnvioParaLoja($pedido['envioParaLoja']);

            $em->persist($enPedido);
            $em->flush();
            $em->commit();

        } catch(\Exception $e) {
            $em->rollback();
            throw new \Exception();
        }

        return $enPedido;
    }

    public function finalizaPedidosByCentral ($PontoTransbordo, $Expedicao)
    {
        $query = "SELECT ped
                    FROM wms:Expedicao\Pedido ped
                   INNER JOIN ped.carga c
                   WHERE c.codExpedicao = $Expedicao
                     AND ped.pontoTransbordo = $PontoTransbordo";

        $pedidos = $this->getEntityManager()->createQuery($query)->getResult();
        foreach ($pedidos as $pedido) {
            $pedido->setConferido(1);
            $this->_em->persist($pedido);
        }
        $this->_em->flush();
    }

    public function findPedidosNaoConferidos ($idExpedicao) {
        $query = "SELECT p
                    FROM wms:Expedicao\Pedido p
              INNER JOIN p.carga c
                   WHERE c.codExpedicao = " . $idExpedicao . "
                     AND (p.conferido = 0  OR p.conferido IS NULL)";

        return  $this->getEntityManager()->createQuery($query)->getResult();
    }

    /**
     * @param $idPedido
     * @return array
     */
    public function findPedidosProdutosSemEtiquetaById($idPedido)
    {
        $query = "SELECT pp
                        FROM wms:Expedicao\PedidoProduto pp
                        INNER JOIN pp.produto p
                        INNER JOIN pp.pedido ped
                        INNER JOIN ped.carga c
                        WHERE ped.id = $idPedido
                        AND ped.id NOT IN (
                          SELECT pp2.codPedido
                            FROM wms:Expedicao\EtiquetaSeparacao ep
                            INNER JOIN wms:Expedicao\PedidoProduto pp2
                            WITH pp2.pedido = ep.pedido
                         )
                        ";

        return  $this->getEntityManager()->createQuery($query)->getResult();
    }

    /**
     * @param $idPedido
     * @param $status
     * @return bool
     * @throws \Exception
     */
    public function gerarEtiquetasById($idPedido, $status)
    {
        $pedidosProdutos = $this->findPedidosProdutosSemEtiquetaById($idPedido);

        if ($pedidosProdutos != null) {
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaSeparacaoRepo */
            $EtiquetaSeparacaoRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');

            if ($EtiquetaSeparacaoRepo->gerarEtiquetas($pedidosProdutos, $status) > 0 ) {
                throw new \Exception ("Existem produtos sem definição de volume");
            }
            return true;
        }
        return false;
    }


    /**
     * @param $idPedido
     * @return mixed
     */
    public function getCargaByPedido($idPedido)
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select('e.id')
            ->from('wms:Expedicao\Pedido', 'p')
            ->innerJoin('p.carga', 'c')
            ->innerJoin('c.expedicao', 'e')
            ->where('p.id = :IdPedido')
            ->setParameter('IdPedido', $idPedido);
        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param $idPedido
     */
    public function cancelar($idPedido)
    {

        try {
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaSeparacaoRepo */
            $EtiquetaSeparacaoRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
            $etiquetas = $EtiquetaSeparacaoRepo->getEtiquetasByPedido($idPedido);

            foreach ($etiquetas as $etiqueta){
                /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao $etiquetaEn */
                $etiquetaEn = $EtiquetaSeparacaoRepo->find($etiqueta['codBarras']);
                if ($etiquetaEn->getCodStatus() <> EtiquetaSeparacao::STATUS_CORTADO) {
                    if (($etiquetaEn->getCodStatus() == EtiquetaSeparacao::STATUS_ETIQUETA_GERADA) ||
                        ($etiquetaEn->getCodStatus() == EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO)) {
                        $EtiquetaSeparacaoRepo->alteraStatus($etiquetaEn, EtiquetaSeparacao::STATUS_CORTADO);
                    } else {
                        $EtiquetaSeparacaoRepo->alteraStatus($etiquetaEn, EtiquetaSeparacao::STATUS_PENDENTE_CORTE);
                    }
                }
            }
            $this->_em->flush();
            $this->gerarEtiquetasById($idPedido, EtiquetaSeparacao::STATUS_CORTADO);
            $this->cancelaPedido($idPedido);

            $expedicao = $this->getCargaByPedido($idPedido);
            $idExpedicao = $expedicao['id'];

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepository  */
            $ExpedicaoRepository = $this->_em->getRepository('wms:Expedicao');
            $pedidosNaoCancelados = $ExpedicaoRepository->countPedidosNaoCancelados($idExpedicao);

            if ($pedidosNaoCancelados == 0) {
                $ExpedicaoEn = $ExpedicaoRepository->find($idExpedicao);
                $ExpedicaoRepository->alteraStatus($ExpedicaoEn, Expedicao::STATUS_CANCELADO);
            }

        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    /**
     * @param $idPedido
     */
    protected function cancelaPedido($idPedido)
    {
        $EntPedido = $this->find($idPedido);
        $EntPedido->setDataCancelamento(new \DateTime());
        $this->_em->persist($EntPedido);
        $this->_em->flush();
    }

    /**
     * @param Pedido $pedidoEntity
     */
    public function remove(Pedido $pedidoEntity) {

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $etiquetas = $EtiquetaRepo->findBy(array('pedido'=>$pedidoEntity));
        foreach($etiquetas as $etiqueta) {
            $this->_em->remove($etiqueta);
            $this->_em->flush();
        }

        /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoRepository $PedidoProdutoRepo */
        $PedidoProdutoRepo = $this->_em->getRepository('wms:Expedicao\PedidoProduto');
        $pedidosProduto = $PedidoProdutoRepo->findBy(array('pedido' => $pedidoEntity->getId()));
        foreach ($pedidosProduto as $pedidoProduto) {
            $this->_em->remove($pedidoProduto);
            $this->_em->flush();
        }

        $this->_em->remove($pedidoEntity);
        $this->_em->flush();
    }


    /**
     * O array de pedidos deve ter a chave o id do pedido e o value a sequencia desejada
     */
    public function realizaSequenciamento(array $pedidos)
    {
        foreach($pedidos as $chave => $sequencia)
        {
            $entityPedido = $this->find($chave);
            $entityPedido->setSequencia($sequencia);
            $this->_em->persist($entityPedido);
        }
       if ($this->_em->flush()) {
           return true;
       }
    }

}