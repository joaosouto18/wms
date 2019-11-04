<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 23/11/2018
 * Time: 15:32
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Wms\Domain\Entity\Produto;

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
     * @SequenceGenerator(sequenceName="SQ_N_INV_CONT_END_PROD_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var InventarioContEndOs $inventarioContEndOs
     * @ManyToOne(targetEntity="Wms\Domain\Entity\InventarioNovo\InventarioContEndOs")
     * @JoinColumn(name="COD_INV_CONT_END_OS", referencedColumnName="COD_INV_CONT_END")
     */
    protected $inventarioContEndOs;

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
     * @var string
     * @Column(name="COD_PRODUTO", type="string")
     */
    protected $codProduto;

    /**
     * @var string
     * @Column(name="DSC_GRADE", type="string")
     */
    protected $grade;

    /**
     * @var string
     * @Column(name="DSC_LOTE", type="string" )
     */
    protected $lote;

    /**
     * @var float
     * @Column(name="QTD_CONTADA", type="decimal" )
     */
    protected $qtdContada;

    /**
     * @var Produto\Embalagem $produtoEmbalagem
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Embalagem")
     * @JoinColumn(name="COD_PRODUTO_EMBALAGEM", referencedColumnName="COD_PRODUTO_EMBALAGEM")
     */
    protected $produtoEmbalagem;

    /**
     * @var float
     * @Column(name="QTD_EMBALAGEM", type="decimal" )
     */
    protected $qtdEmbalagem;

    /**
     * @var string
     * @Column(name="COD_BARRAS", type="string" )
     */
    protected $codBarras;

    /**
     * @var Produto\Volume $produtoVolume
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Volume")
     * @JoinColumn(name="COD_PRODUTO_VOLUME", referencedColumnName="COD_PRODUTO_VOLUME")
     */
    protected $produtoVolume;

    /**
     * @var string
     * @Column(name="IND_DIVERGENTE", type="string" )
     */
    protected $divergente;

    /**
     * @var \DateTime
     * @Column(name="DTH_VALIDADE", type="datetime" )
     */
    protected $validade;

    /**
     * @var \DateTime
     * @Column(name="DTH_CONTAGEM", type="datetime" )
     */
    protected $dthContagem;

    public function __construct()
    {
        $this->setDthContagem();
    }

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
     * @return InventarioContEndOs
     */
    public function getInventarioContEndOs()
    {
        return $this->inventarioContEndOs;
    }

    /**
     * @param InventarioContEndOs $inventarioContEndOs
     */
    public function setInventarioContEndOs($inventarioContEndOs)
    {
        $this->inventarioContEndOs = $inventarioContEndOs;
    }

    /**
     * @return Produto
     */
    public function getProduto()
    {
        return $this->produto;
    }

    /**
     * @param Produto $produto
     */
    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    /**
     * @return string
     */
    public function getCodProduto()
    {
        return $this->codProduto;
    }

    /**
     * @param string $codProduto
     */
    public function setCodProduto($codProduto)
    {
        $this->codProduto = $codProduto;
    }

    /**
     * @return string
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * @param string $grade
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
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
     * @return float
     */
    public function getQtdContada()
    {
        return $this->qtdContada;
    }

    /**
     * @param float $qtdContada
     */
    public function setQtdContada($qtdContada)
    {
        $this->qtdContada = $qtdContada;
    }

    /**
     * @return Produto\Embalagem
     */
    public function getProdutoEmbalagem()
    {
        return $this->produtoEmbalagem;
    }

    /**
     * @param Produto\Embalagem $produtoEmbalagem
     */
    public function setProdutoEmbalagem($produtoEmbalagem)
    {
        $this->produtoEmbalagem = $produtoEmbalagem;
    }

    /**
     * @return float
     */
    public function getQtdEmbalagem()
    {
        return $this->qtdEmbalagem;
    }

    /**
     * @param float $qtdEmbalagem
     */
    public function setQtdEmbalagem($qtdEmbalagem)
    {
        $this->qtdEmbalagem = $qtdEmbalagem;
    }

    /**
     * @return string
     */
    public function getCodBarras()
    {
        return $this->codBarras;
    }

    /**
     * @param string $codBarras
     */
    public function setCodBarras($codBarras)
    {
        $this->codBarras = $codBarras;
    }

    /**
     * @return Produto\Volume
     */
    public function getProdutoVolume()
    {
        return $this->produtoVolume;
    }

    /**
     * @param Produto\Volume $produtoVolume
     */
    public function setProdutoVolume($produtoVolume)
    {
        $this->produtoVolume = $produtoVolume;
    }

    /**
     * @return string
     */
    public function getDivergente()
    {
        return $this->divergente;
    }

    /**
     * @param string $divergente
     */
    public function setDivergente($divergente)
    {
        if (is_null($divergente)) {
            $this->divergente = $divergente;
        } else {
            $this->divergente = ((is_bool($divergente) && $divergente) || (is_string($divergente) && $divergente == 'S') ) ? 'S' : 'N';
        }
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
     * @return \DateTime
     */
    public function getDthContagem()
    {
        return $this->dthContagem;
    }

    private function setDthContagem()
    {
        $this->dthContagem = new \DateTime();
    }
}