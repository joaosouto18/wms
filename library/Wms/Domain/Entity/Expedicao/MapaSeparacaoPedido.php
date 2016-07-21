<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="MAPA_SEPARACAO_PEDIDO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\MapaSeparacaoPedidoRepository")
 */
class MapaSeparacaoPedido
{

    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_MAPA_SEPARACAO_PEDIDO", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_MAPA_SEPARACAO_PEDIDO_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\MapaSeparacao")
     * @JoinColumn(name="COD_MAPA_SEPARACAO", referencedColumnName="COD_MAPA_SEPARACAO")
     */
    protected $mapaSeparacao;

    /**
     * @Column(name="COD_PEDIDO_PRODUTO", type="integer", nullable=false)
     */
    protected $codPedidoProduto;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\PedidoProduto")
     * @JoinColumn(name="COD_PEDIDO_PRODUTO", referencedColumnName="COD_PEDIDO_PRODUTO")
     */
    protected $pedidoProduto;

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
    public function getMapaSeparacao()
    {
        return $this->mapaSeparacao;
    }

    /**
     * @param mixed $mapaSeparacao
     */
    public function setMapaSeparacao($mapaSeparacao)
    {
        $this->mapaSeparacao = $mapaSeparacao;
    }

    /**
     * @return mixed
     */
    public function getCodPedidoProduto()
    {
        return $this->codPedidoProduto;
    }

    /**
     * @param mixed $codPedidoProduto
     */
    public function setCodPedidoProduto($codPedidoProduto)
    {
        $this->codPedidoProduto = $codPedidoProduto;
    }

    /**
     * @return mixed
     */
    public function getPedidoProduto()
    {
        return $this->pedidoProduto;
    }

    /**
     * @param mixed $pedidoProduto
     */
    public function setPedidoProduto($pedidoProduto)
    {
        $this->pedidoProduto = $pedidoProduto;
    }
    
}