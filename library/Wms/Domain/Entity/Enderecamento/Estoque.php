<?php

namespace Wms\Domain\Entity\Enderecamento;
use Wms\Domain\Entity\Produto;


/**
 * Palete
 *
 * @Table(name="ESTOQUE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\EstoqueRepository")
 */
class Estoque
{
    /**
     * @Column(name="COD_ESTOQUE", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ESTOQUE_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var Produto
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @Column(name="DTH_PRIMEIRA_MOVIMENTACAO", type="datetime", nullable=true)
     */
    protected $dtPrimeiraEntrada;

    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     */
    protected $grade;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $depositoEndereco;

    /**
     * @Column(name="QTD", type="decimal", nullable=false)
     */
    protected $qtd;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Armazenagem\Unitizador")
     * @JoinColumn(name="COD_UNITIZADOR", referencedColumnName="COD_UNITIZADOR")
     */

    protected $unitizador;

    /**
     * @Column(name="UMA", type="integer", nullable=false)
     */
    protected $uma;

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
     * @Column(name="DTH_VALIDADE", type="date")
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
     * @param mixed $depositoEndereco
     */
    public function setDepositoEndereco($depositoEndereco)
    {
        $this->depositoEndereco = $depositoEndereco;
    }

    /**
     * @return mixed
     */
    public function getDepositoEndereco()
    {
        return $this->depositoEndereco;
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
     * @param Produto $produto
     */
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
     * @param mixed $dtPrimeiraEntrada
     */
    public function setDtPrimeiraEntrada($dtPrimeiraEntrada)
    {
        $this->dtPrimeiraEntrada = $dtPrimeiraEntrada;
    }

    /**
     * @return mixed
     */
    public function getDtPrimeiraEntrada()
    {
        return $this->dtPrimeiraEntrada;
    }

    /**
     * @param mixed $unitizador
     */
    public function setUnitizador($unitizador)
    {
        $this->unitizador = $unitizador;
    }

    /**
     * @return mixed
     */
    public function getUnitizador()
    {
        return $this->unitizador;
    }

    /**
     * @param mixed $uma
     */
    public function setUma($uma)
    {
        $this->uma = $uma;
    }

    /**
     * @return mixed
     */
    public function getUma()
    {
        return $this->uma;
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
}