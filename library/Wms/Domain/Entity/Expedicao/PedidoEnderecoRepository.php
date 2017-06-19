<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao\PedidoEndereco;

class PedidoEnderecoRepository extends EntityRepository
{

    public function save($pedidoEntity, $pedidoCliente) {

        $em = $this->getEntityManager();
//        $em->beginTransaction();

        try {
            // pegar referência do pedido
            $enPedidoEndereco = new PedidoEndereco();

            $SiglaRepo      = $this->_em->getRepository('wms:Util\Sigla');
            $entitySigla    = $SiglaRepo->findOneBy(array('referencia' => $pedidoCliente['uf']));

            $enPedidoEndereco->setCodPedido($pedidoEntity->getId());
            //LINHA COMENTADA POR RODRIGO PQ NA WILSO SÓ FUNCIONOU DESSA MANEIRA
//            $enPedidoEndereco->setPedido($pedidoEntity);
            $enPedidoEndereco->setIdTipo(\Wms\Domain\Entity\Pessoa\Endereco\Tipo::ENTREGA);
            $enPedidoEndereco->setUf($entitySigla);
            $enPedidoEndereco->setComplemento($pedidoCliente['complemento']);
            $enPedidoEndereco->setDescricao($pedidoCliente['logradouro']);
            $enPedidoEndereco->setPontoReferencia($pedidoCliente['referencia']);
            $enPedidoEndereco->setBairro($pedidoCliente['bairro']);
            $enPedidoEndereco->setLocalidade($pedidoCliente['cidade']);
            $enPedidoEndereco->setNumero($pedidoCliente['numero']);
            $enPedidoEndereco->setCep($pedidoCliente['cep']);

            $em->persist($enPedidoEndereco);
//            $em->flush();
//            $em->commit();
        } catch(\Exception $e) {
//            $em->rollback();
            throw new \Exception($e->getMessage() . ' - ' .$e->getTraceAsString());
        }

        return $enPedidoEndereco;
    }
}