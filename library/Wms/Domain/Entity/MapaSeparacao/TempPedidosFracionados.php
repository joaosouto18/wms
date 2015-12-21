<?php

namespace Wms\Domain\Entity\MapaSeparacao;

/**
 *
 * @Table(name="TEMP_PEDIDOS_FRACIONADOS")
 */
class TempPedidosFracionados
{
    /**
     * @Column(name="COD_PEDIDO", type="integer", nullable=false)
     * @id
     */
    protected $codPedido;

}