<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 23/11/2018
 * Time: 15:32
 */

namespace Wms\Domain\Entity\InventarioNovo;

/**
 * @Table(name="INVENTARIO_CONT_END_PROD")
 * @Entity(repositoryClass="Wms\Domain\Entity\InventarioNovo\InventarioContEndProdRepository")
 */
class InventarioContEndProd
{
    /**
     * @Column(name="COD_INV_CONT_END_PROD", type="integer", length=8, nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_COD_INV_CONT_END_PROD_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\InventarioNovo\InventarioContEnd")
     * @JoinColumn(name="COD_INV_CONT_END", referencedColumnName="COD_INV_CONT_END")
     */
    protected $inventarioContEnd;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO")
     */
    protected $produto;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     */
    protected $grade;

    /**
     * @Column(name="DSC_LOTE", type="varchar" )
     */
    protected $lote;

    /**
     * @Column(name="QTD_CONTADA", type="decimal" )
     */
    protected $qtdContada;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Embalagem")
     * @JoinColumn(name="COD_PRODUTO_EMBALAGEM", referencedColumnName="COD_PRODUTO_EMBALAGEM")
     */
    protected $produtoEmbalagem;

    /**
     * @Column(name="QTD_EMBALAGEM", type="decimal" )
     */
    protected $qtdEmbalagem;

    /**
     * @Column(name="COD_BARRAS", type="varchar" )
     */
    protected $codBarras;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Volume")
     * @JoinColumn(name="COD_PRODUTO_VOLUME", referencedColumnName="COD_PRODUTO_VOLUME")
     */
    protected $produtoVolume;

    /**
     * @Column(name="IND_DIVERGENTE", type="char" )
     */
    protected $divergente;

    /**
     * @Column(name="DTH_VALIDADE", type="datetime" )
     */
    protected $validade;

    /**
     * @Column(name="DTH_CONTAGEM", type="datetime" )
     */
    protected $contagem;


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
    public function getInventarioContEnd()
    {
        return $this->inventarioContEnd;
    }

    /**
     * @param mixed $inventarioContEnd
     */
    public function setInventarioContEnd($inventarioContEnd)
    {
        $this->inventarioContEnd = $inventarioContEnd;
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
    public function getLote()
    {
        return $this->lote;
    }

    /**
     * @param mixed $lote
     */
    public function setLote($lote)
    {
        $this->lote = $lote;
    }

    /**
     * @return mixed
     */
    public function getQtdContada()
    {
        return $this->qtdContada;
    }

    /**
     * @param mixed $qtdContada
     */
    public function setQtdContada($qtdContada)
    {
        $this->qtdContada = $qtdContada;
    }

    /**
     * @return mixed
     */
    public function getProdutoEmbalagem()
    {
        return $this->produtoEmbalagem;
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
    public function getCodBarras()
    {
        return $this->codBarras;
    }

    /**
     * @param mixed $codBarras
     */
    public function setCodBarras($codBarras)
    {
        $this->codBarras = $codBarras;
    }

    /**
     * @return mixed
     */
    public function getProdutoVolume()
    {
        return $this->produtoVolume;
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
    public function getDivergente()
    {
        return $this->divergente;
    }

    /**
     * @param mixed $divergente
     */
    public function setDivergente($divergente)
    {
        $this->divergente = $divergente;
    }

    /**
     * @return mixed
     */
    public function getValidade()
    {
        return $this->validade;
    }

    /**
     * @param mixed $validade
     */
    public function setValidade($validade)
    {
        $this->validade = $validade;
    }

    /**
     * @return mixed
     */
    public function getContagem()
    {
        return $this->contagem;
    }

    /**
     * @param mixed $contagem
     */
    public function setContagem($contagem)
    {
        $this->contagem = $contagem;
    }
}