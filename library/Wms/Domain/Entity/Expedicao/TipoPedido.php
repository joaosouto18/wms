<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 * @Table(name="TIPO_PEDIDO_EXPEDICAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\TipoPedidoRepository")
 */
class TipoPedido
{

    const MOSTRUARIO = 1;
    const REPOSICAO = 2;
    const ENTREGA = 3;
    const SUGESTAO = 4;
    const AVULSO = 5;
    const ASSISTENCIA = 6;
    const KIT = 7;
    const VENDA_BALCAO = 8;
    const SIMPLES_REMESSA = 9;
    const REENTREGA = 10;
    const PEDIDO_ANTECIPADO = 11;
    const OUTROS = 12;
    const CROSS_DOCKING = 13;

    /**
     * @Column(name="COD_TIPO_PEDIDO_EXPEDICAO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_TIPO_PEDIDO_EXPEDICAO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DSC_TIPO_PEDIDO_EXPEDICAO", type="string", length=255, nullable=false)
     */
    protected $descricao;

    /**
     * @Column(name="COD_EXTERNO", type="string", length=30)
     */
    protected $codExterno;

    public function getId()
    {
        return $this->id;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = mb_strtoupper($descricao, 'UTF-8');
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCodExterno()
    {
        return $this->codExterno;
    }

    /**
     * @param mixed $codExterno
     */
    public function setCodExterno($codExterno)
    {
        $this->codExterno = $codExterno;
    }

}