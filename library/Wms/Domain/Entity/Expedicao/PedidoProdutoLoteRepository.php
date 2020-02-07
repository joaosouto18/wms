<?php
/**
 * Created by PhpStorm.
 * User: Luis Fernando
 * Date: 02/05/2018
 * Time: 14:17
 */
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Configurator;
use Wms\Domain\Entity\Ressuprimento\ReservaEstoqueExpedicaoRepository;

class PedidoProdutoLoteRepository extends EntityRepository
{
    public function save($data) {

        $entity = new PedidoProdutoLote();
        Configurator::configure($entity, $data);
        $this->_em->persist($entity);

        return $entity;
    }

    public function generatePedidoProdutoLoteByReserva($arrayItens, ReservaEstoqueExpedicaoRepository $reservaEstExpRepo)
    {
        $pedProdRepo = $this->_em->getRepository("wms:Expedicao\PedidoProduto");
        foreach ($arrayItens as $itemReservado) {
            $pedidoProduto = $pedProdRepo->findOneBy($itemReservado);
            $reservas = $reservaEstExpRepo->getSaldoReservadoByItemPedido($itemReservado);
            foreach ($reservas as $reserva) {
                $pedProdLoteData = [
                    'lote' => $reserva['lote'],
                    'pedidoProduto' => $pedidoProduto,
                    'codPedidoProduto' => $pedidoProduto->getId(),
                    'quantidade' => $reserva['qtd'] * -1,
                    'definicao' => Expedicao\PedidoProdutoLote::DEF_WMS
                ];

                self::save($pedProdLoteData);
            }
        }
    }

}