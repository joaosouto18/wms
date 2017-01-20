<?php

namespace Wms\Domain\Entity\Enderecamento;


/**
 * Palete
 *
 * @Table(name="ESTOQUE_ERP")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\EstoqueErpRepository")
 */
class EstoqueErp
{
    /**
     * @Column(name="COD_ESTOQUE_ERP", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ESTOQUE_ERP_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     */
    protected $grade;

    /**
     * @Column(name="ESTOQUE_GERENCIAL", type="decimal", nullable=false)
     */
    protected $estoqueGerencial;

    /**
     * @Column(name="ESTOQUE_DISPONIVEL", type="decimal", nullable=false)
     */
    protected $estoqueDisponivel;

    /**
     * @Column(name="VLR_ESTOQUE_TOTAL", type="decimal", nullable=false)
     */
    protected $vlrEstoqueTotal;

    /**
     * @Column(name="VLR_ESTOQUE_UNIT", type="decimal", nullable=false)
     */
    protected $vlrEstoqueUnitario;

    /**
     * @Column(name="FATOR_UNIDADE_VENDA", type="decimal", nullable=false)
     */
    protected $fatorUnVenda;

    /**
     * @Column(name="DSC_UNIDADE_VENDA", type="string", nullable=false)
     */
    protected $unVenda;

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
    public function getProduto()
    {
        return $this->produto;
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
    public function getCodProduto()
    {
        return $this->codProduto;
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
    public function getGrade()
    {
        return $this->grade;
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
    public function getEstoqueGerencial()
    {
        return $this->estoqueGerencial;
    }

    /**
     * @param mixed $estoqueGerencial
     */
    public function setEstoqueGerencial($estoqueGerencial)
    {
        $this->estoqueGerencial = $estoqueGerencial;
    }

    /**
     * @return mixed
     */
    public function getEstoqueDisponivel()
    {
        return $this->estoqueDisponivel;
    }

    /**
     * @param mixed $estoqueDisponivel
     */
    public function setEstoqueDisponivel($estoqueDisponivel)
    {
        $this->estoqueDisponivel = $estoqueDisponivel;
    }

    /**
     * @return mixed
     */
    public function getVlrEstoqueTotal()
    {
        return $this->vlrEstoqueTotal;
    }

    /**
     * @param mixed $vlrEstoqueTotal
     */
    public function setVlrEstoqueTotal($vlrEstoqueTotal)
    {
        $this->vlrEstoqueTotal = $vlrEstoqueTotal;
    }

    /**
     * @return mixed
     */
    public function getVlrEstoqueUnitario()
    {
        return $this->vlrEstoqueUnitario;
    }

    /**
     * @param mixed $vlrEstoqueUnitario
     */
    public function setVlrEstoqueUnitario($vlrEstoqueUnitario)
    {
        $this->vlrEstoqueUnitario = $vlrEstoqueUnitario;
    }

    /**
     * @return mixed
     */
    public function getFatorUnVenda()
    {
        return $this->fatorUnVenda;
    }

    /**
     * @param mixed $fatorUnVenda
     */
    public function setFatorUnVenda($fatorUnVenda)
    {
        $this->fatorUnVenda = $fatorUnVenda;
    }

    /**
     * @return mixed
     */
    public function getUnVenda()
    {
        return $this->unVenda;
    }

    /**
     * @param mixed $unVenda
     */
    public function setUnVenda($unVenda)
    {
        $this->unVenda = $unVenda;
    }

}