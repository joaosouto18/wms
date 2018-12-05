<?php

use Bisna\Base\Domain\Entity\Repository as BisnaRepository;

/**
 * 
 */
class Wms_WebService_TipoPedidoExpedicao extends Wms_WebService
{

    /**
     * Adiciona um TipoPedidoExpedicao no WMS
     * 
     * @param string $nome Nome do Tipo de Pedido de Expedição
     * @param string $descricao Descrição do Tipo de Pedido de Expedição
     * @return boolean|Exception se o Tipo de Pedido de Expedicao foi inserido com sucesso ou não
     */
    public function salvar($nome, $descricao)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();

        try {
            $em->beginTransaction();
            
            $tipoPedidoExpedicao  = new Wms\Domain\Entity\Deposito\Expedicao\Pedido\Tipo;
            $tipoPedidoExpedicao->setNome($nome)
                    ->setDescricao($descricao);

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