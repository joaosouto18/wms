<?php

namespace Wms\Domain\Entity\Expedicao;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Carga
 *
 * @Table(name="PEDIDO_PRODUTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\PedidoProdutoRepository")
 */
class PedidoProduto
{

    /**
     * @Id
     * @Column(name="COD_PEDIDO_PRODUTO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PEDIDO_PRODUTO_01", initialValue=1, allocationSize=1)
     **/
    protected $id;
    
    /**
     * @Column(name="COD_PEDIDO", type="integer", nullable=false)
     */
    protected $codPedido;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\Pedido")
     * @JoinColumn(name="COD_PEDIDO", referencedColumnName="COD_PEDIDO")
     */
    protected $pedido;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;    

    /**
     * @Column(name="COD_PRODUTO",type="integer", nullable=false)
     */
    protected $codProduto;        
    
    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     */
    protected $grade;

    /**
     * @Column(name="QUANTIDADE", type="integer", nullable=false)
     */
    protected $quantidade;

    public function setProdutos($produtos)
    {
        $this->produtos = $produtos;
    }

    public function getProdutos()
    {
        return $this->produtos;
    }
        
    public function setGrade($grade)
    {
        $this->grade = $grade;
    }

    public function getGrade()
    {
        return $this->grade;
    }

    public function setPedido($pedido)
    {
        $this->pedido = $pedido;
    }

    public function getPedido()
    {
        return $this->pedido;
    }

    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    public function getProduto()
    {
        return $this->produto;
    }

    public function setQuantidade($quantidade)
    {
        $this->quantidade = $quantidade;
    }

    public function getQuantidade()
    {
        return $this->quantidade;
    }

    public function setCodPedido($codPedido)
    {
        $this->codPedido = $codPedido;
    }

    public function getCodPedido()
    {
        return $this->codPedido;
    }

    public function setCodProduto($codProduto)
    {
        $this->codProduto = $codProduto;
    }

    public function getCodProduto()
    {
        return $this->codProduto;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

}