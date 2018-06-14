<?php

namespace Wms\Domain\Entity\Expedicao;

use Doctrine\Common\Collections\ArrayCollection;
use Wms\Domain\Entity\Produto;

/**
 * Carga
 *
 * @Table(name="PEDIDO_PRODUTO_LOTE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\PedidoProdutoLoteRepository")
 */
class PedidoProdutoLote
{

    /**
     * @Id
     * @Column(name="COD_PEDIDO_PRODUTO_LOTE", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PEDIDO_PRODUTO_LOTE_01", initialValue=1, allocationSize=1)
     **/
    protected $id;

    /**
     * @var PedidoProduto
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\PedidoProduto")
     * @JoinColumn(name="COD_PEDIDO_PRODUTO", referencedColumnName="COD_PEDIDO_PRODUTO")
     */
    protected $pedidoProduto;

    /**
     * @Column(name="COD_PEDIDO_PRODUTO", type="integer", nullable=false)
     */
    protected $codPedidoProduto;

    /**
     * @var Produto\Lote
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Lote")
     * @JoinColumn(name="COD_LOTE", referencedColumnName="COD_LOTE")
     */
    protected $lote;

    /**
     * @Column(name="COD_LOTE", type="integer", nullable=false)
     */
    protected $codLote;

    /**
     * @Column(name="QUANTIDADE", type="decimal", nullable=false)
     */
    protected $quantidade;

    /**
     * @Column(name="QTD_ATENDIDA", type="decimal", nullable=false)
     */
    protected $qtdAtendida;

    /**
     * @Column(name="QTD_CORTE", type="decimal", nullable=false)
     */
    protected $qtdCorte;


    public function setQuantidade($quantidade)
    {
        $this->quantidade = $quantidade;
    }

    public function getQuantidade()
    {
        return $this->quantidade;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $qtdAtendida
     */
    public function setQtdAtendida($qtdAtendida)
    {
        $this->qtdAtendida = $qtdAtendida;
    }

    /**
     * @return mixed
     */
    public function getQtdAtendida()
    {
        return $this->qtdAtendida;
    }

    /**
     * @param mixed $qtdCorte
     */
    public function setQtdCorte($qtdCorte)
    {
        $this->qtdCorte = $qtdCorte;
    }

    /**
     * @return mixed
     */
    public function getQtdCorte()
    {
        return $this->qtdCorte;
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
    public function getCodPedidoProduto()
    {
        return $this->codPedidoProduto;
    }

    /**
     * @return Produto\Lote
     */
    public function getLote()
    {
        return $this->lote;
    }

    /**
     * @param Produto\Lote $lote
     */
    public function setLote($lote)
    {
        $this->lote = $lote;
    }

    /**
     * @param mixed $codLote
     */
    public function setCodLote($codLote)
    {
        $this->codLote = $codLote;
    }

    /**
     * @return mixed
     */
    public function getCodLote()
    {
        return $this->codLote;
    }

    /**
     * @return PedidoProduto
     */
    public function getPedidoProduto()
    {
        return $this->pedidoProduto;
    }

    /**
     * @param PedidoProduto $pedidoProduto
     */
    public function setPedidoProduto($pedidoProduto)
    {
        $this->pedidoProduto = $pedidoProduto;
    }

}