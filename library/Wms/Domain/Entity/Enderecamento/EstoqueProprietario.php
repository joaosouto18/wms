<?php

namespace Wms\Domain\Entity\Enderecamento;


/**
 * Palete
 *
 * @Table(name="ESTOQUE_PROPRIETARIO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\EstoqueProprietarioRepository")
 */
class EstoqueProprietario
{

    const RECEBIMENTO = 1;
    const MOVIMENTACAO = 2;
    const EXPEDICAO = 3;
    const INVENTARIO = 4;

    /**
     * @Column(name="COD_ESTOQUE_PROPRIETARIO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ESTOQUE_PROPRIETARIO", allocationSize=1, initialValue=1)
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
     * @Column(name="DTH_OPERACAO", type="datetime", nullable=true)
     */
    protected $dthOperacao;

    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     */
    protected $grade;

    /**
     * @Column(name="COD_PESSOA", type="integer", nullable=false)
     */
    protected $codPessoa;

    /**
     * @Column(name="QTD", type="decimal", nullable=false)
     */
    protected $qtd;

    /**
     * @Column(name="IND_OPERCAO", type="string", nullable=false)
     */
    protected $operacao;

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
     * @return mixed
     */
    public function getQtd()
    {
        return $this->qtd;
    }

    /**
     * @param mixed $qtd
     */
    public function setQtd($qtd)
    {
        $this->qtd = $qtd;
    }

    /**
     * @param mixed $dthOperacao
     */
    public function setDthOperacao($dthOperacao)
    {
        $this->dthOperacao = $dthOperacao;
    }

    /**
     * @return mixed
     */
    public function getDthOperacao()
    {
        return $this->dthOperacao;
    }

    /**
     * @param mixed $codPessoa
     */
    public function setCodPessoa($codPessoa)
    {
        $this->codPessoa = $codPessoa;
    }

    /**
     * @return mixed
     */
    public function getCodPessoa()
    {
        return $this->codPessoa;
    }

    /**
     * @param mixed $operacao
     */
    public function setOperacao($operacao)
    {
        $this->operacao = $operacao;
    }

    /**
     * @return mixed
     */
    public function getOperacao()
    {
        return $this->operacao;
    }

}