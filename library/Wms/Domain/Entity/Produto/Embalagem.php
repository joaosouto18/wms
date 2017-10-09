<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\Common\Collections\ArrayCollection,
    Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Deposito\Endereco;
use Core\Util\Converter;
$andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
/**
 * Description of Embalagem
 * @Table(name="PRODUTO_EMBALAGEM")
 * @Entity(repositoryClass="Wms\Domain\Entity\Produto\EmbalagemRepository")
 * @author daniel
 */
class Embalagem {

    
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
     * @Column(name="QTD_EMBALAGEM", type="decimal", length=60, nullable=false)
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
     * @Column(name="CAPACIDADE_PICKING", type="decimal", nullable=false)
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

    /**
     * @Column(type="decimal", name="NUM_ALTURA")
     * @var decimal altura do volume
     */
    protected $altura;

    /**
     * @Column(type="decimal", name="NUM_LARGURA")
     * @var decimal largura do volume
     */
    protected $largura;

    /**
     * @Column(type="decimal", name="NUM_PROFUNDIDADE")
     * @var decimal profundidade do volume
     */
    protected $profundidade;

    /**
     * @Column(type="decimal", name="NUM_CUBAGEM")
     * @var decimal cubagem do volume
     */
    protected $cubagem;

    /**
     * @Column(type="decimal", name="NUM_PESO")
     * @var decimal peso do volume
     */
    protected $peso;
    
    public function __construct() {
        $this->dadosLogisticos = new ArrayCollection;
        $this->recebimentoEmbalagens = new ArrayCollection;
    }

    /**
     * Retorna o código da embalagem
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Retorna o produto que esta embalagem pertence
     * @return Produto
     */
    public function getProduto() {
        return $this->produto;
    }

    /**
     * Informa qual o produto que esta embalagem pertence
     * @param Produto $produto 
     */
    public function setProduto(Produto $produto) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Produto', $this->produto, $produto);
        $this->produto = $produto;
        return $this;
    }

    public function getGrade() {
        return $this->grade;
    }

    public function setGrade($grade) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Grade', $this->grade, $grade);
        $this->grade = $grade;
        return $this;
    }

    /**
     * Retorna a descrição (nome) da embalagem
     * @return string
     */
    public function getDescricao() {
        return $this->descricao;
    }

    /**
     * Informa a descrição (nome) da embalagem 
     * @param type $descricao 
     */
    public function setDescricao($descricao) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Descrição Embalagem', $this->descricao, $descricao);
        $this->descricao = $descricao;
        return $this;
    }

    /**
     * Retorna a quantidade que contem nesta embalagem
     * @return integer 
     */
    public function getQuantidade() {
        return $this->quantidade;
    }

    /**
     * Informa a quantidade que contem nesta embalagem
     * @param integer $quantidade 
     */
    public function setQuantidade($quantidade) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Quantidade', $this->quantidade, $quantidade);
        $this->quantidade = $quantidade;
        return $this;
    }

    /**
     * Retorna se a embalagem é padrão
     * @return string
     */
    public function getIsPadrao() {
        return $this->isPadrao;
    }

    /**
     * Informa se esta embalagem usada como padrão pelo produto
     * @param string $isPadrao 
     */
    public function setIsPadrao($isPadrao) {
        if (!in_array($isPadrao, array('S', 'N')))
            throw new \InvalidArgumentException('Valor inválido para Padrao de Embalagem');
        
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Padrão', $this->isPadrao, $isPadrao);

        $this->isPadrao = $isPadrao;
        return $this;
    }

    /**
     * Retorna o código de barras da embalagem
     * @return string
     */
    public function getCodigoBarras() {
        return $this->codigoBarras;
    }

    /**
     * Informa o código de barras da embalagem
     * @param string $codigoBarras 
     */
    public function setCodigoBarras($codigoBarras) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Código de Barras', $this->codigoBarras, $codigoBarras);
        $this->codigoBarras = $codigoBarras;
        return $this;
    }

    public function getDadosLogisticos() {
        return $this->dadosLogisticos;
    }

    public function getRecebimentoEmbalagens() {
        return $this->recebimentoEmbalagens;
    }

    /**
     * @return Endereco
     */
    public function getEndereco() {
        return $this->endereco;
    }

    public function setEndereco($endereco) {

        $dscOrigem = "Nenhum";
        if ($this->endereco != null) {
            if (gettype($this->endereco) == "string") {
                $dscOrigem = $this->endereco;
            } else {
                $dscOrigem = $this->endereco->getDescricao();
            }
        }

        $dscDestino = "Nenhum";
        if ($endereco != null) {
            if (gettype($endereco) == "string") {
                $dscDestino = $endereco;
            } else {
                $dscDestino = $endereco->getDescricao();
            }
        }

        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Endereço de Picking', $dscOrigem, $dscDestino);

        $this->endereco = $endereco;

        return $this;
    }

    public function getCBInterno() {
        return $this->CBInterno;
    }

    public function setCBInterno($CBInterno) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Código de barras interno', $this->CBInterno, $CBInterno);
        $this->CBInterno = $CBInterno;
        return $this;
    }

    public function getImprimirCB() {
        return $this->imprimirCB;
    }

    public function setImprimirCB($imprimirCB) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Imprimir código de barras interno', $this->imprimirCB, $imprimirCB);
        $this->imprimirCB = $imprimirCB;
        return $this;
    }

    public function setEmbalado($embalado) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Embalado', $this->embalado, $embalado);
        $this->embalado = $embalado;
    }

    public function getEmbalado() {
        return $this->embalado;
    }

    /**
     * @param mixed $capacidadePicking
     */
    public function setCapacidadePicking($capacidadePicking) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Capacidade de Picking', $this->capacidadePicking, $capacidadePicking);

        $this->capacidadePicking = str_replace(',', '.', $capacidadePicking);
    }

    /**
     * @return mixed
     */
    public function getCapacidadePicking() {
        return str_replace('.', ',', $this->capacidadePicking);
    }

    /**
     * @param mixed $pontoReposicao
     */
    public function setPontoReposicao($pontoReposicao) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Ponto de Reposição', $this->pontoReposicao, $pontoReposicao);
        $this->pontoReposicao = $pontoReposicao;
    }

    /**
     * @return mixed
     */
    public function getPontoReposicao() {
        return $this->pontoReposicao;
    }

    /**
      <<<<<<< HEAD
     * @return datetime
     */
    public function getDataInativacao() {
        return $this->dataInativacao;
    }

    /**
     * @param datetime $dataInativacao
     */
    public function setDataInativacao($dataInativacao) {
        $this->dataInativacao = $dataInativacao;
    }

    /**
     * @return int
     */
    public function getUsuarioInativacao() {
        return $this->usuarioInativacao;
    }

    /**
     * @param int $usuarioInativacao
     */
    public function setUsuarioInativacao($usuarioInativacao) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Usuario inativação', $this->usuarioInativacao, $usuarioInativacao);
        $this->usuarioInativacao = $usuarioInativacao;
    }

    /**
     * @return string
     */
    public function getCodProduto() {
        return $this->codProduto;
    }

    /**
     * @param string $codProduto
     */
    public function setCodProduto($codProduto) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Código do Produto', $this->codProduto, $codProduto);
        $this->codProduto = $codProduto;
    }
    
    /**
     * Retorna a altura do produto
     * @return decimal
     */
    public function getAltura() {
        return Converter::enToBr($this->altura, 3);
    }

    /**
     * Informa a altura do volume
     * @param decimal $altura 
     */
    public function setAltura($altura) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Altura', number_format($this->altura, 3, ',', ''), $altura);
        $this->altura = Converter::brToEn($altura, 3);
        return $this;
    }

    /**
     * Retorna a largura do volume
     * @return decimal
     */
    public function getLargura() {
        return Converter::enToBr($this->largura, 3);
    }

    /**
     * Informa a largura do volume
     * @param decimal $largura 
     */
    public function setLargura($largura) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Largura', number_format($this->largura, 3, ',', ''), $largura);
        $this->largura = Converter::brToEn($largura, 3);
        return $this;
    }

    /**
     * Retorna a profundidade do volume
     * @return decimal
     */
    public function getProfundidade() {
        return Converter::enToBr($this->profundidade, 3);
    }

    /**
     * Informa a profundidade do volume
     * @param decimal $profundidade 
     */
    public function setProfundidade($profundidade) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Profundidade',  number_format($this->profundidade, 3, ',', ''), $profundidade);
        $this->profundidade = Converter::brToEn($profundidade, 3);
        return $this;
    }

    /**
     * Retorna a cubagem do volume
     * @return decimal
     */
    public function getCubagem() {
        return Converter::enToBr($this->cubagem, 4);
    }

    /**
     * Informa a cubagem do volume
     * @param decimal $cubagem 
     */
    public function setCubagem($cubagem) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Cubagem', number_format($this->cubagem, 4, ',', ''), $cubagem);
        $this->cubagem = Converter::brToEn($cubagem, 4);
        return $this;
    }

    /**
     * Retorna o peso do volume
     * @return decimal
     */
    public function getPeso() {
        return Converter::enToBr($this->peso, 3);
    }

    /**
     * Informa o peso do volume
     * @param decimal $peso 
     * @param bool $importacao
     */
    public function setPeso($peso, $importacao = null) {
        if (empty($importacao)) {
            $this->peso = Converter::brToEn($peso, 3);
        } else {
            $this->peso = $peso;
        }
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Peso', $this->peso, $peso);
        return $this;
    }

}
