<?php

namespace Wms\Domain\Entity;

use Wms\Domain\Entity\ProdutoRepository,
    Wms\Domain\Entity\Fabricante,
    Wms\Domain\Entity\Produto\Classe,
    Wms\Domain\Entity\Produto\TipoComercializacao,
    Wms\Domain\Entity\Produto\Volume,
    Wms\Domain\Entity\Produto\Embalagem,
    Wms\Domain\Entity\Armazenagem\LinhaSeparacao,
    Doctrine\Common\Collections\ArrayCollection;

/**
 * Produtos no WMS
 *
 * @Table(name="PRODUTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\ProdutoRepository")
 */
class Produto {

    const UNID_MEDIDA_KILOGRAMA = 'KG';
    const UNID_MEDIDA_LITRO = 'L';
    const UNID_MEDIDA_METRO = 'M';

    public static $listaUnidadeMedida = array(
        self::UNID_MEDIDA_KILOGRAMA => 'QUILO',
        self::UNID_MEDIDA_LITRO => 'LITRO',
        self::UNID_MEDIDA_METRO => 'METRO',
    );


    const TIPO_UNITARIO = 1;
    const TIPO_COMPOSTO = 2;
    const TIPO_KIT = 3;

    /**
     * @var array lista de tipos de produtos
     */
    public static $listaTipo = array(
        self::TIPO_UNITARIO => 'Unitário',
        self::TIPO_COMPOSTO => 'Composto',
        self::TIPO_KIT => 'Kit',
    );

    /**
     * @Column(name="ID_PRODUTO", type="integer", nullable=false)
     */
    protected $idProduto;

    /**
     * @Id
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     * @var string Código do produto
     */
    protected $id;

    /**
     * @Id
     * @var string Grade do produto
     * @Column(name="DSC_GRADE", type="string", length=255, nullable=false)
     */
    protected $grade;

    /**
     * @Column(name="DSC_PRODUTO", type="string", length=255, nullable=false)
     * @var string Descrição
     */
    protected $descricao;

    /**
     * @Column(name="DSC_REFERENCIA", type="string", length=255, nullable=false)
     * @var string Referencia
     */
    protected $referencia;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Fabricante", cascade={"persist"})
     * @JoinColumn(name="COD_FABRICANTE", referencedColumnName="COD_FABRICANTE")
     * @var Wms\Domain\Entity\Fabricante Fabricante do Produto
     */
    protected $fabricante;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Classe", cascade={"persist"})
     * @JoinColumn(name="COD_PRODUTO_CLASSE", referencedColumnName="COD_PRODUTO_CLASSE")
     * @var Wms\Domain\Entity\Produto\Classe Classe do Produto
     */
    protected $classe;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\TipoComercializacao")
     * @JoinColumn(name="COD_TIPO_COMERCIALIZACAO", referencedColumnName="COD_TIPO_COMERCIALIZACAO")
     * @var Wms\Domain\Entity\Produto\TipoComercializacao TipoComercializacao do Produto
     */
    protected $tipoComercializacao;

    /**
     * @Column(name="COD_BARRAS_BASE", type="string", length=128)
     * @var string código de barras base
     */
    protected $codigoBarrasBase;

    /**
     * @Column(name="NUM_VOLUMES", type="integer", nullable=false)
     * @var integer numero de volumes do produto
     */
    protected $numVolumes;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Produto\Volume", mappedBy="produto", cascade={"persist", "remove"})
     * @var ArrayCollection volumes que compoem este produto
     */
    protected $volumes;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Produto\Embalagem", mappedBy="produto", cascade={"persist", "remove"})
     * @var ArrayCollection embalagens que contém este produto 
     */
    protected $embalagens;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Armazenagem\LinhaSeparacao", cascade={"persist"})
     * @JoinColumn(name="COD_LINHA_SEPARACAO", referencedColumnName="COD_LINHA_SEPARACAO")
     * @var LinhaSeparacao 
     */
    protected $linhaSeparacao;

    /**
     * @Column(name="DIAS_VIDA_UTIL", type="integer")
     * @var int
     */
    protected $diasVidaUtil;

    /**
     * @Column(name="DIAS_VIDA_UTIL_MAX", type="integer")
     * @var int
     */
    protected $diasVidaUtilMax;

    /**
     * @Column(name="POSSUI_VALIDADE", type="string")
     * @var string
     */
    protected $validade;

    /**
     * @Column(name="PERC_TOLERANCIA", type="float", nullable=false)
     * @var float
     */
    protected $percTolerancia;

    /**
     * @Column(name="TOLERANCIA_NOMINAL", type="float", nullable=false)
     * @var float
     */
    protected $toleranciaNominal;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco", cascade={"persist"})
     * @JoinColumn(name="COD_ENDERECO_REF_END_AUTO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $enderecoReferencia;

    /**
     * @Column(name="IND_POSSUI_PESO_VARIAVEL", type="string", nullable=false)
     * @var string
     */
    protected $possuiPesoVariavel;

    /**
     * @var
     * @Column(name="IND_FRACIONAVEL", type="string", nullable=false)
     */
    protected $indFracionavel;

    /**
     * @var
     * @Column(name="UNID_FRACAO", type="string", nullable=false)
     */
    protected $unidadeFracao;

    /**
     * @var string
     * @Column(name="IND_CONTROLA_LOTE", type="string", nullable=false)
     */
    protected $indControlaLote;

    /**
     * @var string
     * @Column(name="IND_FORCA_EMB_VENDA", type="string", nullable=false)
     */
    protected $forcarEmbVenda;

    public function __construct() {
        $this->volumes = new ArrayCollection;
        $this->embalagens = new ArrayCollection;
    }

    /**
     * Retorna o código do produto
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Id', $this->id, $id);
        $this->id = $id;
        return $this;
    }

    /**
     * Retorna a descrição do produto
     * @return string
     */
    public function getDescricao() {
        return $this->descricao;
    }

    /**
     * Informa a descriação (nome) do produto
     * @param string $descricao 
     */
    public function setDescricao($descricao) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Descrição do produto', $this->descricao, $descricao);
        $this->descricao = $descricao;
        return $this;
    }

    /**
     * Retorna a grade do produto
     * @return string
     */
    public function getGrade() {
        return $this->grade;
    }

    /**
     * Informa a grade do produto
     * @param string $grade 
     */
    public function setGrade($grade) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Grade', $this->grade, $grade);
        $this->grade = $grade;
        return $this;
    }

    /**
     * Retorna o fabricante do produto
     * @return Fabricante
     */
    public function getFabricante() {
        return $this->fabricante;
    }

    /**
     * Informa o fabricante do produto
     * @param Fabricante $fabricante 
     */
    public function setFabricante(Fabricante $fabricante) {
        $this->fabricante = $fabricante;
        return $this;
    }

    /**
     * Retorna a classe que o produto pertence 
     * @return Classe
     */
    public function getClasse() {
        return $this->classe;
    }

    /**
     * Informa a qual classe o produto pertence
     * @param Classe $classe 
     */
    public function setClasse(Classe $classe) {
        $this->classe = $classe;
        return $this;
    }

    /**
     * Retorna a tipoComercializacao que o produto pertence 
     * @return TipoComercializacao
     */
    public function getTipoComercializacao() {
        return $this->tipoComercializacao;
    }

    /**
     * Informa a qual tipoComercializacao o produto pertence
     * @param TipoComercializacao $tipoComercializacao 
     */
    public function setTipoComercializacao(TipoComercializacao $tipoComercializacao) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Tipo de Comercialização', ( $this->tipoComercializacao == null ? null : $this->tipoComercializacao->getDescricao()), $tipoComercializacao->getDescricao());
        $this->tipoComercializacao = $tipoComercializacao;
        return $this;
    }

    public function getCodigoBarrasBase() {
        return $this->codigoBarrasBase;
    }

    public function setCodigoBarrasBase($codigoBarrasBase) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Código de barras base', $this->codigoBarrasBase, $codigoBarrasBase);
        $this->codigoBarrasBase = $codigoBarrasBase;
        return $this;
    }

    public function getReferencia() {
        return $this->referencia;
    }

    public function setReferencia($referencia) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Referência', $this->referencia, $referencia);
        $this->referencia = $referencia;
        return $this;
    }

    /**
     * Adicona um volume que compoe o produto
     * @param Volume $volume 
     */
    public function addVolume(Volume $volume) {
        //$codigoSequencial = count($this->volumes);
        //$codigoSequencial++;
        //$volume->setCodigoSequencial($codigoSequencial);
        $volume->setProduto($this);
        $this->volumes[] = $volume;
        //$this->setNumVolumes($codigoSequencial);
        return $this;
    }

    /**
     * 
     */
    public function getVolumes() {
        return $this->volumes;
    }

    /**
     * Adiciona uma embalagem que contem o produto
     * @param Embalagem $embalagem 
     */
    public function addEmbalagem(Embalagem $embalagem) {
        $embalagem->setProduto($this);
        $this->embalagens[] = $embalagem;
        return $this;
    }

    /**
     * 
     */
    public function getEmbalagens() {
        return $this->embalagens;
    }

    /**
     * Retorna a linha de separação a qual o produto pertence
     * @return LinhaSeparacao
     */
    public function getLinhaSeparacao() {
        return $this->linhaSeparacao;
    }

    /**
     * Informa a linha de separação a qual o produto pertence
     * @param LinhaSeparacao $linhaSeparacao 
     */
    public function setLinhaSeparacao(LinhaSeparacao $linhaSeparacao) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Linha de Separação', ( $this->linhaSeparacao == null ? null : $this->linhaSeparacao->getDescricao()), $linhaSeparacao->getDescricao());
        $this->linhaSeparacao = $linhaSeparacao;
        return $this;
    }

    public function getNumVolumes() {
        return $this->numVolumes;
    }

    public function setNumVolumes($numVolumes) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Número Volumes', $this->numVolumes, $numVolumes);
        $this->numVolumes = $numVolumes;
        return $this;
    }

    /**
     * @return int
     */
    public function getDiasVidaUtil() {
        return $this->diasVidaUtil;
    }

    /**
     * @param int $diasVidaUtil
     * @return Produto
     */
    public function setDiasVidaUtil($diasVidaUtil) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Dias vida Util', $this->diasVidaUtil, $diasVidaUtil);
        $this->diasVidaUtil = $diasVidaUtil;
        return $this;
    }

    /**
     * @return int
     */
    public function getDiasVidaUtilMax() {
        return $this->diasVidaUtilMax;
    }

    /**
     * @param int $diasVidaUtilMax
     * @return Produto
     */
    public function setDiasVidaUtilMax($diasVidaUtilMax) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Dias vida Util Max', $this->diasVidaUtilMax, $diasVidaUtilMax);
        $this->diasVidaUtilMax = $diasVidaUtilMax;
        return $this;
    }

    /**
     * @return string
     */
    public function getValidade() {
        return $this->validade;
    }

    /**
     * @param string $validade
     * @return Produto
     */
    public function setValidade($validade) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Validade', $this->validade, $validade);
        $this->validade = $validade;
        return $this;
    }

    /**
     * @param mixed $enderecoReferencia
     */
    public function setEnderecoReferencia($enderecoReferencia) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Endereço Referencia', $this->enderecoReferencia, $enderecoReferencia);
        $this->enderecoReferencia = $enderecoReferencia;
    }

    /**
     * @return mixed
     */
    public function getEnderecoReferencia() {
        return $this->enderecoReferencia;
    }

    /**
     * @param float $percTolerancia
     */
    public function setPercTolerancia($percTolerancia) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Percentual de Tolerancia', $this->percTolerancia, $percTolerancia);
        $this->percTolerancia = $percTolerancia;
    }

    /**
     * @return float
     */
    public function getPercTolerancia() {
        return $this->percTolerancia;
    }

    /**
     * @param float $toleranciaNominal
     */
    public function setToleranciaNominal($toleranciaNominal) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Tolerancia Nominal', $this->toleranciaNominal, $toleranciaNominal);
        $this->toleranciaNominal = $toleranciaNominal;
    }

    /**
     * @return float
     */
    public function getToleranciaNominal() {
        return $this->toleranciaNominal;
    }

    /**
     * @return mixed
     */
    public function getIdProduto() {
        return $this->idProduto;
    }

    /**
     * @param mixed $idProduto
     */
    public function setIdProduto($idProduto) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Id Produto', $this->idProduto, $idProduto);
        $this->idProduto = $idProduto;
    }

    /**
     * @return string
     */
    public function getPossuiPesoVariavel() {
        return $this->possuiPesoVariavel;
    }

    /**
     * @param string $possuiPesoVariavel
     * @return Produto
     */
    public function setPossuiPesoVariavel($possuiPesoVariavel) {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Possui peso variavel', $this->possuiPesoVariavel, $possuiPesoVariavel);
        $this->possuiPesoVariavel = $possuiPesoVariavel;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIndFracionavel()
    {
        return $this->indFracionavel;
    }

    /**
     * @param mixed $indFracionavel
     * @return Produto
     */
    public function setIndFracionavel($indFracionavel)
    {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this, 'Unidade fracionável', $this->indFracionavel, $indFracionavel);
        $this->indFracionavel = $indFracionavel;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUnidadeFracao()
    {
        return $this->unidadeFracao;
    }

    /**
     * @param mixed $unidadeFracao
     * @return Produto
     */
    public function setUnidadeFracao($unidadeFracao)
    {
        $this->unidadeFracao = $unidadeFracao;
        return $this;
    }

    /**
     * @return string
     */
    public function getIndControlaLote()
    {
        return $this->indControlaLote;
    }

    /**
     * @param string $indControlaLote
     * @return Produto
     */
    public function setIndControlaLote($indControlaLote)
    {
        $this->indControlaLote = $indControlaLote;
        return $this;
    }

    /**
     * @return string
     */
    public function getForcarEmbVenda()
    {
        return $this->forcarEmbVenda;
    }

    /**
     * @param string $forcarEmbVenda
     * @return Produto
     */
    public function setForcarEmbVenda($forcarEmbVenda)
    {
        $this->forcarEmbVenda = $forcarEmbVenda;
        return $this;
    }
}
