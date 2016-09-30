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
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
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
     * @Column(name="QTD_EMBALAGEM", type="decimal", nullable=false)
     */
    protected $qtdEmbalagem;

    /**
     * @Column(name="QTD_SEPARAR", type="decimal", nullable=false)
     */
    protected $qtdSeparar;

    /**
     * @Column(name="QTD_CORTADO", type="decimal", nullable=false)
     */
    protected $qtdCortado;

    /**
     * @Column(name="IND_CONFERIDO", type="string", nullable=true)
     */
    protected $indConferido;

    /**
     * @Column(name="COD_PEDIDO_PRODUTO", type="integer", nullable=false)
     */
    protected $codPedidoProduto;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\PedidoProduto")
     * @JoinColumn(name="COD_PEDIDO_PRODUTO", referencedColumnName="COD_PEDIDO_PRODUTO")
     */
    protected $pedidoProduto;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
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
     * @return mixed
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

}