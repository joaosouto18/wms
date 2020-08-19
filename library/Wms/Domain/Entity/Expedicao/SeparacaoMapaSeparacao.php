<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="SEPARACAO_MAPA_SEPARACAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\SeparacaoMapaSeparacaoRepository")
 */
class SeparacaoMapaSeparacao
{

    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_SEPARACAO_MAPA_SEPARACAO", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_SEPARACAO_MAPA_SEPARACAO_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @Column(name="COD_OS", type="integer", nullable=false)
     */
    protected $codOs;

    /**
     * @Column(name="COD_MAPA_SEPARACAO", type="integer", nullable=false)
     */
    protected $codMapaSeparacao;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @Column(name="COD_PRODUTO", type="integer", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="integer", nullable=false)
     */
    protected $grade;

    /**
     * @Column(name="COD_PRODUTO_VOLUME", type="integer", nullable=false)
     */
    protected $codProdutoVolume;

    /**
     * @Column(name="COD_PRODUTO_EMBALAGEM", type="integer", nullable=true)
     */
    protected $codProdutoEmbalagem;

    /**
     * @Column(name="QTD_EMBALAGEM", type="integer", nullable=true)
     */
    protected $qtdEmbalagem;

    /**
     * @Column(name="QTD_SEPARADA", type="decimal", nullable=true)
     */
    protected $qtdSeparada;

    /**
     * @Column(name="DTH_SEPARACAO", type="datetime", nullable=false)
     */
    protected $dthSeparacao;

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
     * @return mixed
     */
    public function getCodOs()
    {
        return $this->codOs;
    }

    /**
     * @param mixed $codOs
     */
    public function setCodOs($codOs)
    {
        $this->codOs = $codOs;
    }

    /**
     * @return mixed
     */
    public function getCodMapaSeparacao()
    {
        return $this->codMapaSeparacao;
    }

    /**
     * @param mixed $codMapaSeparacao
     */
    public function setCodMapaSeparacao($codMapaSeparacao)
    {
        $this->codMapaSeparacao = $codMapaSeparacao;
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
     * @return mixed
     */
    public function getCodProdutoVolume()
    {
        return $this->codProdutoVolume;
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
    public function getCodProdutoEmbalagem()
    {
        return $this->codProdutoEmbalagem;
    }

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
    public function getQtdEmbalagem()
    {
        return $this->qtdEmbalagem;
    }

    /**
     * @param mixed $qtdEmbalagem
     */
    public function setQtdEmbalagem($qtdEmbalagem)
    {
        $this->qtdEmbalagem = $qtdEmbalagem;
    }

    /**
     * @return mixed
     */
    public function getQtdSeparada()
    {
        return $this->qtdSeparada;
    }

    /**
     * @param mixed $qtdSeparada
     */
    public function setQtdSeparada($qtdSeparada)
    {
        $this->qtdSeparada = $qtdSeparada;
    }

    /**
     * @return mixed
     */
    public function getDthSeparacao()
    {
        return $this->dthSeparacao;
    }

    /**
     * @param mixed $dthSeparacao
     */
    public function setDthSeparacao($dthSeparacao)
    {
        $this->dthSeparacao = $dthSeparacao;
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