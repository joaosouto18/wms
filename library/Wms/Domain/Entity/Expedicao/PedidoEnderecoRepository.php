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

            if (!isset($entitySigla) || empty($entitySigla)) {
                throw new \Exception('Sigla para estado inválida');
            }
            $enPedidoEndereco->setCodPedido($pedidoEntity->getId());
            $enPedidoEndereco->setPedido($pedidoEntity);
            $enPedidoEndereco->setIdTipo(\Wms\Domain\Entity\Pessoa\Endereco\Tipo::ENTREGA);
            $enPedidoEndereco->setUf($entitySigla);
            $enPedidoEndereco->setComplemento($pedidoCliente['complemento']);
            $enPedidoEndereco->setDescricao($pedidoCliente['logradouro']);
            $enPedidoEndereco->setPontoReferencia($pedidoCliente['referencia']);
            $enPedidoEndereco->setBairro($pedidoCliente['bairro']);
            $enPedidoEndereco->setLocalidade($pedidoCliente['cidade']);
            $enPedidoEndereco->setNumero($pedidoCliente['numero']);

            if (isset($pedidoCliente['cep']) && ($pedidoCliente['cep'] != null)) {
                $enPedidoEndereco->setCep($pedidoCliente['cep']);
            } else {
                $enPedidoEndereco->setCep(null);
            }


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