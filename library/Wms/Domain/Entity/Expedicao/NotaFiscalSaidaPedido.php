<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="NOTA_FISCAL_SAIDA_PEDIDO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\NotaFiscalSaidaPedidoRepository")
 */
class NotaFiscalSaidaPedido
{

    /**
     * @Id
     * @Column(name="COD_NOTA_FISCAL_SAIDA_PEDIDO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_NF_SAIDA_PEDIDO_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @Column(name="COD_PEDIDO", type="integer",nullable=false)
     */
    protected $codPedido;

    /**
     * @Column(name="COD_NOTA_FISCAL_SAIDA", type="integer",nullable=false)
     */
    protected $codNotaFiscalSaida;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\NotaFiscalSaida")
     * @JoinColumn(name="COD_NOTA_FISCAL_SAIDA", referencedColumnName="COD_NOTA_FISCAL_SAIDA")
     */
    protected $notaFiscalSaida;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\Pedido")
     * @JoinColumn(name="COD_PEDIDO", referencedColumnName="COD_PEDIDO")
     */
    protected $pedido;

}