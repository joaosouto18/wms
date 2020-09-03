<?php

/**
 * 
 */
class Wms_WebService_TipoPedidoExpedicao extends Wms_WebService
{

    /**
     * Adiciona um TipoPedidoExpedicao no WMS
     *
     * @param string $descricao Descrição do Tipo de Pedido de Expedição
     * @param string $codExterno Código do Tipo de Pedido no ERP
     * @return boolean|Exception se o Tipo de Pedido de Expedicao foi inserido com sucesso ou não
     */
    public function salvar($descricao, $codExterno)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();

        try {
            $em->beginTransaction();
            
            $tipoPedidoExpedicao  = new Wms\Domain\Entity\Expedicao\TipoPedido();
            $tipoPedidoExpedicao->setDescricao($descricao)
                    ->setCodExterno($codExterno);

            $em->persist($tipoPedidoExpedicao);
            $em->flush();
            $em->commit();

            return true;
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    /**
     * Retorna uma matriz com todas os tipos de pedido expedicao cadastrados no WMS
     * 
     * @return array|Exception
     */
    public function listar()
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();

        $result = $em->createQueryBuilder()
                ->select('t')
                ->from('wms:Deposito\Expedicao\Pedido\Tipo', 't')
                ->orderBy('t.nome')
                ->getQuery()
                ->getArrayResult();

        return $result;
    }

}