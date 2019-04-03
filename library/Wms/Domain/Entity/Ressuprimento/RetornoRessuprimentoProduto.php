<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 22/03/2019
 * Time: 14:38
 */

namespace Wms\Domain\Entity\Ressuprimento;

use Wms\Domain\Entity\Deposito;
use Bisna\Base\Domain\Entity\EntityService;
use Doctrine\Common\Collections\Criteria;
use Wms\Domain\Entity\Atividade;
use Wms\Domain\Entity\Enderecamento\EstoqueRepository;
use Wms\Domain\Entity\Enderecamento\HistoricoEstoque;
use Wms\Domain\Entity\InventarioNovo;
use Wms\Domain\Entity\InventarioNovoRepository;
use Wms\Domain\Entity\OrdemServico;
use Wms\Domain\Entity\OrdemServicoRepository;
use Wms\Domain\Entity\Pessoa;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Usuario;
use Wms\Module\Expedicao\Report\RelatorioEtiquetaEmbalados;


/**
 * @Table(name="RETORNO_RESSUPRIMENTO_PRODUTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\RetornoRessuprimentoProduto")
 */
class RetornoRessuprimentoProduto
{
    /**
     * @Id
     * @Column(name="NUM_SEQUENCIA", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RETORNO_RESSUP_PROD_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var RetornoRessuprimento $retornoRessuprimento
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Ressuprimento\RetornoRessuprimento")
     * @JoinColumn(name="COD_RETORNO_RESSUPRIMENTO", referencedColumnName="COD_RETORNO_RESSUPRIMENTO")
     */
    protected $retornoRessuprimento;

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
     * @var Produto\Embalagem $produtoEmbalagem
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Embalagem")
     * @JoinColumn(name="COD_PRODUTO_EMBALAGEM", referencedColumnName="COD_PRODUTO_EMBALAGEM")
     */
    protected $produtoEmbalagem;

    /**
     * @var Produto\Volume $produtoVolume
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Volume")
     * @JoinColumn(name="COD_PRODUTO_VOLUME", referencedColumnName="COD_PRODUTO_VOLUME")
     */
    protected $produtoVolume;

    /**
     * @Column(name="DTH_MOVIMENTACAO", type="datetime", nullable=false)
     */
    protected $qtd;

    /**
     * @var string
     * @Column(name="DSC_LOTE", type="string" )
     */
    protected $lote;


    /**
     * @param mixed $retornoRessuprimento
     */
    public function setRetornoRessuprimento($retornoRessuprimento)
    {
        $this->retornoRessuprimetno = $retornoRessuprimento;
    }

    /**
     * @return mixed
     */
    public function getRetornoRessuprimento()
    {
        return $this->retornoRessuprimetno;
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
     * @param mixed $lote
     */
    public function setLote($lote)
    {
        $this->lote = $lote;
    }

    /**
     * @return mixed
     */
    public function getLote()
    {
        return $this->lote;
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

    public function toArray()
    {
        return Configurator::configureToArray($this);
    }


}
