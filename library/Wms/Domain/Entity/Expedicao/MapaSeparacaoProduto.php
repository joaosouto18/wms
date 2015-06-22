<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="MAPA_SEPARACAO_PRODUTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository")
 */
class MapaSeparacaoProduto
{

    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_MAPA_SEPARACAO_PRODUTO", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_MAPA_SEPARACAO_PROD_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\MapaSeparacao")
     * @JoinColumn(name="COD_MAPA_SEPARACAO", referencedColumnName="COD_MAPA_SEPARACAO")
     */
    protected $mapaSeparacao;

    /**
     * @Column(name="COD_PRODUTO", type="integer", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     */
    protected $dscGrade;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

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
     * @Column(name="QTD_EMBALAGEM", type="integer", nullable=false)
     */
    protected $qtdEmbalagem;

    /**
     * @Column(name="QTD_SEPARAR", type="integer", nullable=false)
     */
    protected $qtdSeparar;

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
     * @param mixed $dscGrade
     */
    public function setDscGrade($dscGrade)
    {
        $this->dscGrade = $dscGrade;
    }

    /**
     * @return mixed
     */
    public function getDscGrade()
    {
        return $this->dscGrade;
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
     * @param mixed $mapaSeparacao
     */
    public function setMapaSeparacao($mapaSeparacao)
    {
        $this->mapaSeparacao = $mapaSeparacao;
    }

    /**
     * @return mixed
     */
    public function getMapaSeparacao()
    {
        return $this->mapaSeparacao;
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
     * @param mixed $qtdEmbalagem
     */
    public function setQtdEmbalagem($qtdEmbalagem)
    {
        $this->qtdEmbalagem = $qtdEmbalagem;
    }

    /**
     * @return mixed
     */
    public function getQtdEmbalagem()
    {
        return $this->qtdEmbalagem;
    }

    /**
     * @param mixed $qtdSeparar
     */
    public function setQtdSeparar($qtdSeparar)
    {
        $this->qtdSeparar = $qtdSeparar;
    }

    /**
     * @return mixed
     */
    public function getQtdSeparar()
    {
        return $this->qtdSeparar;
    }
}