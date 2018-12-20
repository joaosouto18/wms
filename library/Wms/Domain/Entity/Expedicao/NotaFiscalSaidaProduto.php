<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="NOTA_FISCAL_SAIDA_PRODUTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\NotaFiscalSaidaProdutoRepository")
 */
class NotaFiscalSaidaProduto
{

    /**
     * @Id
     * @Column(name="COD_NOTA_FISCAL_SAIDA_PRODUTO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_NF_SAIDA_PRODUTO_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @Column(name="COD_PRODUTO", type="string",nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string",nullable=false)
     */
    protected $grade;

    /**
     * @Column(name="QUANTIDADE", type="decimal", nullable=false)
     */
    protected $quantidade;

    /**
     * @Column(name="VALOR_VENDA", type="decimal",nullable=false)
     */
    protected $valorVenda;

    /**
     * @Column(name="COD_NOTA_FISCAL_SAIDA", type="integer",nullable=false)
     */
    protected $codNotaFiscalSaida;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\NotaFiscalSaida")
     * @JoinColumn(name="COD_NOTA_FISCAL_SAIDA", referencedColumnName="COD_NOTA_FISCAL_SAIDA")
     */
    protected $notaFiscalSaida;

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
    public function getCodNotaFiscalSaida()
    {
        return $this->codNotaFiscalSaida;
    }

    /**
     * @param mixed $codProduto
     */
    public function setCodProduto($codProduto)
    {
        $this->codProduto = $codProduto;
    }

    /**
     * @return mixed
     */
    public function getCodProduto()
    {
        return $this->codProduto;
    }

    /**
     * @param mixed $grade
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
    }

    /**
     * @return mixed
     */
    public function getGrade()
    {
        return $this->grade;
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
    public function getId()
    {
        return $this->id;
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
    public function getNotaFiscalSaida()
    {
        return $this->notaFiscalSaida;
    }

    /**
     * @param mixed $produto
     */
    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    /**
     * @return mixed
     */
    public function getProduto()
    {
        return $this->produto;
    }

    /**
     * @param mixed $quantidade
     */
    public function setQuantidade($quantidade)
    {
        $this->quantidade = $quantidade;
    }

    /**
     * @return mixed
     */
    public function getQuantidade()
    {
        return $this->quantidade;
    }

    /**
     * @param mixed $valorVenda
     */
    public function setValorVenda($valorVenda)
    {
        $this->valorVenda = $valorVenda;
    }

    /**
     * @return mixed
     */
    public function getValorVenda()
    {
        return $this->valorVenda;
    }

}