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

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getPedido()
    {
        return $this->pedido;
    }

    /**
     * @param mixed $pedido
     */
    public function setPedido($pedido)
    {
        $this->pedido = $pedido;
    }

    /**
     * @return mixed
     */
    public function getNotaFiscalSaida()
    {
        return $this->notaFiscalSaida;
    }

    /**
     * @param mixed $notaFiscalSaida
     */
    public function setNotaFiscalSaida($notaFiscalSaida)
    {
        $this->notaFiscalSaida = $notaFiscalSaida;
    }

    /**
     * @return mixed
     */
    public function getCodNotaFiscalSaida()
    {
        return $this->codNotaFiscalSaida;
    }

    /**
     * @param mixed $codNotaFiscalSaida
     */
    public function setCodNotaFiscalSaida($codNotaFiscalSaida)
    {
        $this->codNotaFiscalSaida = $codNotaFiscalSaida;
    }

    /**
     * @return mixed
     */
    public function getCodPedido()
    {
        return $this->codPedido;
    }

    /**
     * @param mixed $codPedido
     */
    public function setCodPedido($codPedido)
    {
        $this->codPedido = $codPedido;
    }

}