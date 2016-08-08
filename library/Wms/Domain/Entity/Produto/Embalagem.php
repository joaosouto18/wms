<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\Common\Collections\ArrayCollection,
    Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Deposito\Endereco;

/**
 * Description of Embalagem
 * @Table(name="PRODUTO_EMBALAGEM")
 * @Entity
 * @author daniel
 */
class Embalagem
{

    /**
     * @Id
     * @Column(name="COD_PRODUTO_EMBALAGEM", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PRODUTO_EMBALAGEM_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     *
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     *
     * @var Produto produto no qual esta embalagem pertence
     */
    protected $produto;

    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     * @var string Código do produto
     */
    protected $codProduto;

    /**
     * @var string Grade do produto
     * @Column(name="DSC_GRADE", type="string", length=10, nullable=false)
     */
    protected $grade;

    /**
     * @Column(name="DSC_EMBALAGEM", type="string", length=60, nullable=false)
     * @var string descrição (nome) da embalagem
     */
    protected $descricao;

    /**
     * @Column(name="QTD_EMBALAGEM", type="integer", length=60, nullable=false)
     * @var integer quantidade de itens esta embalagem contém
     */
    protected $quantidade;

    /**
     * @Column(name="IND_PADRAO", type="string", length=60, nullable=false)
     * @var string se a embalagem é padrão ou não
     */
    protected $isPadrao;

    /**
     * @Column(name="IND_CB_INTERNO", type="string", length=1, nullable=false)
     * @var string Indicador se deve ou não gerar Gerar codigo de barra interno
     */
    protected $CBInterno;

    /**
     * @Column(name="IND_IMPRIMIR_CB", type="string", length=1, nullable=false)
     * @var string Indicador se deve ou não gerar imprimir o codigo de barra no recebimento
     */
    protected $imprimirCB;

    /**
     * @Column(name="COD_BARRAS", type="string", length=60, nullable=false)
     * @var string código de barras da embalagem
     */
    protected $codigoBarras;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Produto\DadoLogistico", mappedBy="embalagem", cascade={"remove"})
     * @var ArrayCollection volumes que compoem este produto
     */
    protected $dadosLogisticos;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Recebimento\Embalagem", mappedBy="embalagem")
     * @var ArrayCollection lista de recebimentos desta embalagem
     */
    protected $recebimentoEmbalagens;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $endereco;

    /**
     * @var string $ind_embalado
     * @Column(name="IND_EMBALADO", type="string", length=30, nullable=false)
     */
    protected $embalado;

    /**
     * @Column(name="PONTO_REPOSICAO", type="integer", nullable=false)
     */
    protected $pontoReposicao;

    /**
     * @Column(name="CAPACIDADE_PICKING", type="integer", nullable=false)
     */
    protected $capacidadePicking;
    
    /**
     * @Column(name="DTH_INATIVACAO", type="datetime", nullable=true)
     * @var datetime
     */
    protected $dataInativacao;

    /**
     * @Column(name="COD_USUARIO_INATIVACAO", type="integer", nullable=false)
     * @var int
     */
    protected $usuarioInativacao;
    
    public function __construct()
    {
        $this->dadosLogisticos = new ArrayCollection;
        $this->recebimentoEmbalagens = new ArrayCollection;
    }

    /**
     * Retorna o código da embalagem
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Retorna o produto que esta embalagem pertence
     * @return Produto
     */
    public function getProduto()
    {
        return $this->produto;
    }

    /**
     * Informa qual o produto que esta embalagem pertence
     * @param Produto $produto 
     */
    public function setProduto(Produto $produto)
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

    /**
     * Retorna a descrição (nome) da embalagem
     * @return string
     */
    public function getDescricao()
    {
        return $this->descricao;
    }

    /**
     * Informa a descrição (nome) da embalagem 
     * @param type $descricao 
     */
    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
        return $this;
    }

    /**
     * Retorna a quantidade que contem nesta embalagem
     * @return integer 
     */
    public function getQuantidade()
    {
        return $this->quantidade;
    }

    /**
     * Informa a quantidade que contem nesta embalagem
     * @param integer $quantidade 
     */
    public function setQuantidade($quantidade)
    {
        $this->quantidade = $quantidade;
        return $this;
    }

    /**
     * Retorna se a embalagem é padrão
     * @return string
     */
    public function getIsPadrao()
    {
        return $this->isPadrao;
    }

    /**
     * Informa se esta embalagem usada como padrão pelo produto
     * @param string $isPadrao 
     */
    public function setIsPadrao($isPadrao)
    {
        if (!in_array($isPadrao, array('S', 'N')))
            throw new \InvalidArgumentException('Valor inválido para Padrao de Embalagem');

        $this->isPadrao = $isPadrao;
        return $this;
    }

    /**
     * Retorna o código de barras da embalagem
     * @return string
     */
    public function getCodigoBarras()
    {
        return $this->codigoBarras;
    }

    /**
     * Informa o código de barras da embalagem
     * @param string $codigoBarras 
     */
    public function setCodigoBarras($codigoBarras)
    {
        $this->codigoBarras = $codigoBarras;
        return $this;
    }

    public function getDadosLogisticos()
    {
        return $this->dadosLogisticos;
    }

    public function getRecebimentoEmbalagens()
    {
        return $this->recebimentoEmbalagens;
    }

    public function getEndereco()
    {
        return $this->endereco;
    }

    public function setEndereco($endereco)
    {
        $this->endereco = $endereco;
        return $this;
    }

    public function getCBInterno()
    {
        return $this->CBInterno;
    }

    public function setCBInterno($CBInterno)
    {
        $this->CBInterno = $CBInterno;
        return $this;
    }

    public function getImprimirCB()
    {
        return $this->imprimirCB;
    }

    public function setImprimirCB($imprimirCB)
    {
        $this->imprimirCB = $imprimirCB;
        return $this;
    }

    public function setEmbalado($embalado)
    {
        $this->embalado = $embalado;
    }

    public function getEmbalado()
    {
        return $this->embalado;
    }

    /**
     * @param mixed $capacidadePicking
     */
    public function setCapacidadePicking($capacidadePicking)
    {
        $this->capacidadePicking = $capacidadePicking;
    }

    /**
     * @return mixed
     */
    public function getCapacidadePicking()
    {
        return $this->capacidadePicking;
    }

    /**
     * @param mixed $pontoReposicao
     */
    public function setPontoReposicao($pontoReposicao)
    {
        $this->pontoReposicao = $pontoReposicao;
    }

    /**
     * @return mixed
     */
    public function getPontoReposicao()
    {
        return $this->pontoReposicao;
    }

    /**
<<<<<<< HEAD
     * @return datetime
     */
    public function getDataInativacao()
    {
        return $this->dataInativacao;
    }

    /**
     * @param datetime $dataInativacao
     */
    public function setDataInativacao($dataInativacao)
    {
        $this->dataInativacao = $dataInativacao;
    }

    /**
     * @return int
     */
    public function getUsuarioInativacao()
    {
        return $this->usuarioInativacao;
    }

    /**
     * @param int $usuarioInativacao
     */
    public function setUsuarioInativacao($usuarioInativacao)
    {
        $this->usuarioInativacao = $usuarioInativacao;
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
    
}