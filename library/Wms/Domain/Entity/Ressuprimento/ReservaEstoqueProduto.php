<?php

namespace Wms\Domain\Entity\Ressuprimento;
/**
 * @Table(name="RESERVA_ESTOQUE_PRODUTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\ReservaEstoqueProdutoRepository")
 */
class ReservaEstoqueProduto
{
    /**
     * @Id
     * @Column(name="COD_RESERVA_ESTOQUE_PRODUTO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RESERVA_ESTOQUE_PRODUTO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var ReservaEstoque
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Ressuprimento\ReservaEstoque")
     * @JoinColumn(name="COD_RESERVA_ESTOQUE", referencedColumnName="COD_RESERVA_ESTOQUE")
     */
    protected $reservaEstoque;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @Column(name="COD_PRODUTO", type="decimal", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="decimal", nullable=false)
     */
    protected $grade;

    /**
     * Qtd Reservada (Positivo para Reserva de Entrada, Negativo para Reserva de Saida)
     * @Column(name="QTD_RESERVADA", type="decimal", nullable=false)
     */
    protected $qtd;

    /**
     * Qtd Reservada (Reserva de estoque original durante a criação das reservas)
     * @Column(name="QTD_RESERVADA_ORIGINAL", type="decimal", nullable=false)
     */
    protected $qtdOriginal;

    /**
     * @Column(name="COD_PRODUTO_EMBALAGEM", type="integer",  nullable=true)
     */
    protected $codProdutoEmbalagem;

    /**
     * @Column(name="COD_PRODUTO_VOLUME", type="integer",  nullable=true)
     */
    protected $codProdutoVolume;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Embalagem")
     * @JoinColumn(name="COD_PRODUTO_EMBALAGEM", referencedColumnName="COD_PRODUTO_EMBALAGEM")
     */
    protected $produtoEmbalagem;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Volume")
     * @JoinColumn(name="COD_PRODUTO_VOLUME", referencedColumnName="COD_PRODUTO_VOLUME")
     */
    protected $produtoVolume;

    /**
     * @Column(name="DTH_VALIDADE", type="date", nullable=true)
     * @var \DateTime
     */
    protected $validade;

    /**
     * @Column(name="NUM_PECAS", type="integer")
     * @var integer
     */
    protected $numPecas;

    /**
     * @Column(name="DSC_LOTE", type="string")
     * @var string
     */
    protected $lote;


    /**
     * @param mixed $codProdutoEmbalagem
     */
    public function setCodProdutoEmbalagem($codProdutoEmbalagem)
    {
        $this->codProdutoEmbalagem = $codProdutoEmbalagem;
    }

    /**
     * @return mixed
     */
    public function getCodProdutoEmbalagem()
    {
        return $this->codProdutoEmbalagem;
    }

    /**
     * @param mixed $codProdutoVolume
     */
    public function setCodProdutoVolume($codProdutoVolume)
    {
        $this->codProdutoVolume = $codProdutoVolume;
    }

    /**
     * @return mixed
     */
    public function getCodProdutoVolume()
    {
        return $this->codProdutoVolume;
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
     * @param mixed $qtd
     */
    public function setQtd($qtd)
    {
        $this->qtd = $qtd;
    }

    /**
     * @return mixed
     */
    public function getQtd()
    {
        return $this->qtd;
    }

    /**
     * @param ReservaEstoque $reservaEstoque
     */
    public function setReservaEstoque($reservaEstoque)
    {
        $this->reservaEstoque = $reservaEstoque;
    }

    /**
     * @return ReservaEstoque
     */
    public function getReservaEstoque()
    {
        return $this->reservaEstoque;
    }

    /**
     * @param mixed $produtoEmbalagem
     */
    public function setProdutoEmbalagem($produtoEmbalagem)
    {
        $this->produtoEmbalagem = $produtoEmbalagem;
    }

    /**
     * @return mixed
     */
    public function getProdutoEmbalagem()
    {
        return $this->produtoEmbalagem;
    }

    /**
     * @param mixed $produtoVolume
     */
    public function setProdutoVolume($produtoVolume)
    {
        $this->produtoVolume = $produtoVolume;
    }

    /**
     * @return mixed
     */
    public function getProdutoVolume()
    {
        return $this->produtoVolume;
    }

    /**
     * @return \DateTime
     */
    public function getValidade()
    {
        return $this->validade;
    }

    /**
     * @param \DateTime $validade
     */
    public function setValidade($validade)
    {
        $this->validade = $validade;
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
     * @return int
     */
    public function getNumPecas()
    {
        return $this->numPecas;
    }

    /**
     * @param int $numPecas
     */
    public function setNumPecas($numPecas)
    {
        $this->numPecas = $numPecas;
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

    /**
     * @return mixed
     */
    public function getQtdOriginal()
    {
        return $this->qtdOriginal;
    }

    /**
     * @param mixed $qtdOriginal
     */
    public function setQtdOriginal($qtdOriginal)
    {
        $this->qtdOriginal = $qtdOriginal;
    }

}
