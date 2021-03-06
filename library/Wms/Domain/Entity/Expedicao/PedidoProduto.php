<?php

namespace Wms\Domain\Entity\Expedicao;

use Doctrine\Common\Collections\ArrayCollection;
use Wms\Domain\Entity\Produto;

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
     * @Column(name="COD_PRODUTO",type="string", nullable=false)
     */
    protected $codProduto;
    
    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     */
    protected $grade;

    /**
     * @Column(name="QUANTIDADE", type="decimal", nullable=false)
     */
    protected $quantidade;

    /**
     * @Column(name="QTD_ATENDIDA", type="decimal", nullable=false)
     */
    protected $qtdAtendida;

    /**
     * @Column(name="QTD_CORTADA", type="decimal", nullable=false)
     */
    protected $qtdCortada;

    /**
     * @Column(name="VALOR_VENDA", type="decimal", nullable=false)
     */
    protected $valorVenda;
    
    /**
     * @Column(name="QTD_CORTADO_AUTOMATICO", type="decimal")
     */
    protected $qtdCortadoAutomatico;

    /**
     * @Column(name="FATOR_EMBALAGEM_VENDA", type="decimal", nullable=false)
     */
    protected $fatorEmbalagemVenda;

    /**
     * @Column(name="COD_MOTIVO_CORTE", type="integer", nullable=true)
     */
    protected $codMotivoCorte;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\MotivoCorte")
     * @JoinColumn(name="COD_MOTIVO_CORTE", referencedColumnName="COD_MOTIVO_CORTE")
     */
    protected $motivoCorte;

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

    /**
     * @return Pedido
     */
    public function getPedido()
    {
        return $this->pedido;
    }

    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    /**
     * @return Produto
     */
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
     * @param mixed $qtdCortada
     */
    public function setQtdCortada($qtdCortada)
    {
        $this->qtdCortada = $qtdCortada;
    }

    /**
     * @return mixed
     */
    public function getQtdCortada()
    {
        return $this->qtdCortada;
    }

    /**
     * @param mixed $valorVenda
     */
    public function setValorVenda($valorVenda)
    {
        $this->valorVenda = 0;
        //$this->valorVenda = $valorVenda;
    }

    /**
     * @return mixed
     */
    public function getValorVenda()
    {
        return $this->valorVenda;
    }

     /**
     * @param mixed $qtdCortadoAutomatico
     */
    public function setQtdCortadoAutomatico($qtdCortadoAutomatico)
    {
        $this->qtdCortadoAutomatico = $qtdCortadoAutomatico;
    }

    /**
     * @return mixed
     */
    public function getQtdCortadoAutomatico()
    {
        return $this->qtdCortadoAutomatico;
    }

    /**
     * @return mixed
     */
    public function getFatorEmbalagemVenda()
    {
        return $this->fatorEmbalagemVenda;
    }

    /**
     * @param mixed $fatorEmbalagemVenda
     */
    public function setFatorEmbalagemVenda($fatorEmbalagemVenda)
    {
        $this->fatorEmbalagemVenda = $fatorEmbalagemVenda;
    }

    /**
     * @return mixed
     */
    public function getCodMotivoCorte()
    {
        return $this->codMotivoCorte;
    }

    /**
     * @param mixed $codMotivoCorte
     */
    public function setCodMotivoCorte($codMotivoCorte)
    {
        $this->codMotivoCorte = $codMotivoCorte;
    }

    /**
     * @return mixed
     */
    public function getMotivoCorte()
    {
        return $this->motivoCorte;
    }

    /**
     * @param mixed $motivoCorte
     */
    public function setMotivoCorte($motivoCorte)
    {
        $this->motivoCorte = $motivoCorte;
    }

}
