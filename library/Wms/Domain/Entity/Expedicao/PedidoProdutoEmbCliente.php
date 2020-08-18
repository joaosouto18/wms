<?php

namespace Wms\Domain\Entity\Expedicao;

use Doctrine\Common\Collections\ArrayCollection;
use Wms\Domain\Entity\Produto;

/**
 * Pedido Produto Emb Cliente
 *
 * @Table(name="PEDIDO_PRODUTO_EMB_CLIENTE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\PedidoProdutoEmbClienteRepository")
 */
class PedidoProdutoEmbCliente
{
    /**
     * @Id
     * @Column(name="COD_PEDIDO_PRODUTO_EMB_CLIENTE", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PEDIDO_PRODUTO_EMB_CLIENTE", initialValue=1, allocationSize=1)
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
     * @Column(name="COD_MAPA_SEPARACAO_EMBALADO", type="integer", nullable=false)
     */
    protected $codMapaSeparacaoEmbalado;

    /**
     * @Column(name="QTD", type="decimal", nullable=false)
     */
    protected $quantidade;

    /**
     * @Column(name="DSC_LOTE", type="string")
     * @var string
     */
    protected $lote;

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
    public function getCodMapaSeparacaoEmbalado()
    {
        return $this->codMapaSeparacaoEmbalado;
    }

    /**
     * @param mixed $codMapaSeparacaoEmbalado
     */
    public function setCodMapaSeparacaoEmbalado($codMapaSeparacaoEmbalado)
    {
        $this->codMapaSeparacaoEmbalado = $codMapaSeparacaoEmbalado;
    }

    /**
     * @return mixed
     */
    public function getQuantidade()
    {
        return $this->quantidade;
    }

    /**
     * @param mixed $quantidade
     */
    public function setQuantidade($quantidade)
    {
        $this->quantidade = $quantidade;
    }

    /**
     * @return string
     */
    public function getLote()
    {
        return $this->lote;
    }

    /**
     * @param string $lote
     */
    public function setLote($lote)
    {
        $this->lote = $lote;
    }

}
