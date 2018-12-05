<?php

namespace Wms\Domain\Entity\Ressuprimento;

use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Expedicao\Pedido;

/**
 * @Table(name="RESERVA_ESTOQUE_EXPEDICAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\ReservaEstoqueExpedicaoRepository")
 */
class ReservaEstoqueExpedicao
{
    const SAIDA_SEM_CONTROLE_ESTOQUE = 0;
    const SAIDA_PICKING = 1;
    const SAIDA_SEPARACAO_AEREA = 2;
    const SAIDA_PULMAO_DOCA = 3;

    public static $tipoSaidaTxt = array(
        self::SAIDA_SEM_CONTROLE_ESTOQUE => "SEM CONTROLE DE ESTOQUE",
        self::SAIDA_PICKING => "PICKING",
        self::SAIDA_SEPARACAO_AEREA => "SEPARAÇÃO AÉREA",
        self::SAIDA_PULMAO_DOCA => "PULMÃO-DOCA"
    );

    /**
     * @var ReservaEstoque
     * @Id
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Ressuprimento\ReservaEstoque")
     * @JoinColumn(name="COD_RESERVA_ESTOQUE", referencedColumnName="COD_RESERVA_ESTOQUE")
     */
    protected $reservaEstoque;

    /**
     * @var Expedicao
     * @Id
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao")
     * @JoinColumn(name="COD_EXPEDICAO", referencedColumnName="COD_EXPEDICAO")
     */
    protected $expedicao;

    /**
     * @var Pedido
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\Pedido")
     * @JoinColumn(name="COD_PEDIDO", referencedColumnName="COD_PEDIDO")
     */
    protected $pedido;

    /**
     * @var string
     * @Column(name="QUEBRA_PULMAO_DOCA", type="string", nullable=false)
     */
    protected $quebraPulmaoDoca;

    /**
     * @var string
     * @Column(name="COD_CRITERIO_QUEBRA_PD", type="string", nullable=false)
     */
    protected $codCriterioPD;

    /**
     * @var string
     * @Column(name="TIPO_SAIDA", type="string", nullable=false)
     */
    protected $tipoSaida;

    /**
     * @return ReservaEstoque
     */
    public function getReservaEstoque()
    {
        return $this->reservaEstoque;
    }

    /**
     * @param ReservaEstoque $reservaEstoque
     */
    public function setReservaEstoque($reservaEstoque)
    {
        $this->reservaEstoque = $reservaEstoque;
    }

    /**
     * @return Expedicao
     */
    public function getExpedicao()
    {
        return $this->expedicao;
    }

    /**
     * @param Expedicao $expedicao
     */
    public function setExpedicao($expedicao)
    {
        $this->expedicao = $expedicao;
    }

    /**
     * @return Pedido
     */
    public function getPedido()
    {
        return $this->pedido;
    }

    /**
     * @param Pedido $pedido
     */
    public function setPedido($pedido)
    {
        $this->pedido = $pedido;
    }

    /**
     * @return string
     */
    public function getQuebraPulmaoDoca()
    {
        return $this->quebraPulmaoDoca;
    }

    /**
     * @param string $quebraPulmaoDoca
     */
    public function setQuebraPulmaoDoca($quebraPulmaoDoca)
    {
        $this->quebraPulmaoDoca = $quebraPulmaoDoca;
    }

    /**
     * @return string
     */
    public function getCodCriterioPD()
    {
        return $this->codCriterioPD;
    }

    /**
     * @param string $codCriterioPD
     */
    public function setCodCriterioPD($codCriterioPD)
    {
        $this->codCriterioPD = $codCriterioPD;
    }

    /**
     * @return string
     */
    public function getTipoSaida()
    {
        return $this->tipoSaida;
    }

    /**
     * @param string $tipoSaida
     */
    public function setTipoSaida($tipoSaida)
    {
        $this->tipoSaida = $tipoSaida;
    }
}
