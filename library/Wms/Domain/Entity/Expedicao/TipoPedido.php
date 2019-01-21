<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 * @Table(name="TIPO_PEDIDO_EXPEDICAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\TipoPedidoRepository")
 */
class TipoPedido
{

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