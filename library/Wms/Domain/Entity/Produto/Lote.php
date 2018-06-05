<?php

namespace Wms\Domain\Entity\Produto;

use Wms\Domain\Entity\Produto as ProdutoEntity;


/**
 * Description of Lote
 * @Table(name="LOTE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Produto\LoteRepository")
 */
class Lote
{

    /**
     * @Column(name="COD_LOTE", type="integer", nullable=false)
     * @Id
     * @var integer
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
     * @var string Código do produto
     */
    protected $codProduto;

    /**
     * @var string Grade do produto
     * @Column(name="DSC_GRADE", type="string", length=255, nullable=false)
     */
    protected $grade;

    /**
     * @Column(name="DSC_LOTE", type="string", length=255)
     */
    protected $descricao;

    /**
     * @Column(name="COD_PESSOA_CRIACAO", type="string", nullable=false)
     * @var string Código da pessoa
     */
    protected $codPessoaCriacao;

    /**
     * @Column(name="DTH_CRIACAO", type="datetime", nullable=true)
     * @var \datetime
     */
    protected $dthCriacao;


    /**
     * @var string Origem
     * @Column(name="IND_ORIGEM_LOTE", type="string", length=255, nullable=false)
     */
    protected $origem;


    /**
     * Retorna o código do lote
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    /**
     * Retorna o produto no qual este lote compõe
     * @return Produto
     */
    public function getProduto()
    {
        return $this->produto;
    }

    /**
     * Informa o produto no qual este lote compõe
     * @param Produto $produto
     */
    public function setProduto(ProdutoEntity $produto)
    {
        $this->produto = $produto;
        return $this;
    }

    public function getGrade()
    {
        return $this->grade;
    }

    public function setGrade($grade)
    {
        $this->grade = $grade;
        return $this;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
        return $this;
    }

    public function getCodPessoaCriacao()
    {
        return $this->codPessoaCriacao;
    }

    public function setCodPessoaCriacao($codPessoaCriacao)
    {
        $this->codPessoaCriacao = $codPessoaCriacao;
        return $this;
    }

    public function getDthCriacao()
    {
        return $this->dthCriacao;
    }

    public function setDthCriacao($dthCriacao)
    {
        $this->dthCriacao = $dthCriacao;
        return $this;
    }

    public function getOrigem()
    {
        return $this->origem;
    }

    public function setOrigem($origem)
    {
        $this->origem = $origem;
        return $this;
    }
}
