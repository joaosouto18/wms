<?php

namespace Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Produto\Embalagem;
use Wms\Domain\Entity\Produto\Volume;

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
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     */
    protected $dscGrade;

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
     * @Column(name="QTD_EMBALAGEM", type="float", nullable=false)
     */
    protected $qtdEmbalagem;

    /**
     * @Column(name="QTD_SEPARAR", type="float", nullable=false)
     */
    protected $qtdSeparar;

    /**
     * @Column(name="QTD_CORTADO", type="float", nullable=false)
     */
    protected $qtdCortado;

    /**
     * @Column(name="IND_CONFERIDO", type="string", nullable=true)
     */
    protected $indConferido;

    /**
     * @Column(name="IND_SEPARADO", type="string", nullable=true)
     */
    protected $indSeparado;

    /**
     * @Column(name="COD_PEDIDO_PRODUTO", type="integer", nullable=false)
     */
    protected $codPedidoProduto;

    /**
     * @var PedidoProduto
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\PedidoProduto")
     * @JoinColumn(name="COD_PEDIDO_PRODUTO", referencedColumnName="COD_PEDIDO_PRODUTO")
     */
    protected $pedidoProduto;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $depositoEndereco;

    /**
     * * @Column(name="COD_DEPOSITO_ENDERECO", type="integer", nullable=false)
     */
    protected $codDepositoEndereco;

    /**
     * @Column(name="NUM_CAIXA_PC_INI", type="integer", nullable=true)
     */
    protected $numCaixaInicio;

    /**
     * @Column(name="NUM_CAIXA_PC_FIM", type="integer", nullable=true)
     */
    protected $numCaixaFim;

    /**
     * @Column(name="CUBAGEM_TOTAL", nullable=true)
     */
    protected $cubagem;

    /**
     * @Column(name="NUM_CARRINHO", type="integer", nullable=true)
     * @var int
     */
    protected $numCarrinho;

    /**
     * @Column(name="IND_DIVERGENCIA", nullable=true)
     */
    protected $divergencia;

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
     * @return MapaSeparacao
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
     * @return Produto
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
     * @return Embalagem
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
     * @return Volume
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

    /**
     * @param mixed $indConferido
     */
    public function setIndConferido($indConferido)
    {
        $this->indConferido = $indConferido;
    }

    /**
     * @return mixed
     */
    public function getIndConferido()
    {
        return $this->indConferido;
    }

    /**
     * @param mixed $indSeparado
     */
    public function setIndSeparado($indSeparado)
    {
        $this->indSeparado = $indSeparado;
    }

    /**
     * @return mixed
     */
    public function getIndSeparado()
    {
        return $this->indSeparado;
    }

    /**
     * @param mixed $codPedidoProduto
     */
    public function setCodPedidoProduto($codPedidoProduto)
    {
        $this->codPedidoProduto = $codPedidoProduto;
    }

    /**
     * @return mixed
     */
    public function getCodPedidoProduto()
    {
        return $this->codPedidoProduto;
    }

    /**
     * @return mixed
     */
    public function getDepositoEndereco()
    {
        return $this->depositoEndereco;
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
    public function getCodDepositoEndereco()
    {
        return $this->codDepositoEndereco;
    }

    /**
     * @param mixed $codDepositoEndereco
     */
    public function setCodDepositoEndereco($codDepositoEndereco)
    {
        $this->codDepositoEndereco = $codDepositoEndereco;
    }

    /**
     * @param mixed $qtdCortado
     */
    public function setQtdCortado($qtdCortado)
    {
        $this->qtdCortado = $qtdCortado;
    }

    /**
     * @return mixed
     */
    public function getQtdCortado()
    {
        return $this->qtdCortado;
    }

    /**
     * @return PedidoProduto
     */
    public function getPedidoProduto()
    {
        return $this->pedidoProduto;
    }

    /**
     * @param mixed $pedidoProduto
     */
    public function setPedidoProduto($pedidoProduto)
    {
        $this->pedidoProduto = $pedidoProduto;
    }

    /**
     * @return mixed
     */
    public function getNumCaixaInicio()
    {
        return $this->numCaixaInicio;
    }

    /**
     * @param mixed $numCaixaInicio
     */
    public function setNumCaixaInicio($numCaixaInicio)
    {
        $this->numCaixaInicio = $numCaixaInicio;
    }

    /**
     * @return mixed
     */
    public function getNumCaixaFim()
    {
        return $this->numCaixaFim;
    }

    /**
     * @param mixed $numCaixaFim
     */
    public function setNumCaixaFim($numCaixaFim)
    {
        $this->numCaixaFim = $numCaixaFim;
    }

    /**
     * @return mixed
     */
    public function getCubagem()
    {
        return $this->cubagem;
    }

    /**
     * @param mixed $cubagem
     */
    public function setCubagem($cubagem)
    {
        $this->cubagem = $cubagem;
    }

    /**
     * @return int
     */
    public function getNumCarrinho()
    {
        return $this->numCarrinho;
    }

    /**
     * @param int $numCarrinho
     */
    public function setNumCarrinho($numCarrinho)
    {
        $this->numCarrinho = $numCarrinho;
    }

    /**
     * @return mixed
     */
    public function getDivergencia()
    {
        return $this->divergencia;
    }

    /**
     * @param mixed $divergencia
     */
    public function setDivergencia($divergencia)
    {
        $this->divergencia = $divergencia;
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