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
class Produto
{

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
     * @Id
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     * @var string Código do produto
     */
    protected $id;

    /**
     * @Id
     * @var string Grade do produto
     * @Column(name="DSC_GRADE", type="string", length=10, nullable=false)
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
     * @Column(name="POSSUI_VALIDADE", type="string")
     * @var string
     */
    protected $validade;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco", cascade={"persist"})
     * @JoinColumn(name="COD_ENDERECO_REF_END_AUTO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $enderecoReferencia;

    public function __construct()
    {
        $this->volumes = new ArrayCollection;
        $this->embalagens = new ArrayCollection;
    }

    /**
     * Retorna o código do produto
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Retorna a descrição do produto
     * @return string
     */
    public function getDescricao()
    {
        return $this->descricao;
    }

    /**
     * Informa a descriação (nome) do produto
     * @param string $descricao 
     */
    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
        return $this;
    }

    /**
     * Retorna a grade do produto
     * @return string
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * Informa a grade do produto
     * @param string $grade 
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
        return $this;
    }

    /**
     * Retorna o fabricante do produto
     * @return Fabricante
     */
    public function getFabricante()
    {
        return $this->fabricante;
    }

    /**
     * Informa o fabricante do produto
     * @param Fabricante $fabricante 
     */
    public function setFabricante(Fabricante $fabricante)
    {
        $this->fabricante = $fabricante;
        return $this;
    }

    /**
     * Retorna a classe que o produto pertence 
     * @return Classe
     */
    public function getClasse()
    {
        return $this->classe;
    }

    /**
     * Informa a qual classe o produto pertence
     * @param Classe $classe 
     */
    public function setClasse(Classe $classe)
    {
        $this->classe = $classe;
        return $this;
    }

    /**
     * Retorna a tipoComercializacao que o produto pertence 
     * @return TipoComercializacao
     */
    public function getTipoComercializacao()
    {
        return $this->tipoComercializacao;
    }

    /**
     * Informa a qual tipoComercializacao o produto pertence
     * @param TipoComercializacao $tipoComercializacao 
     */
    public function setTipoComercializacao(TipoComercializacao $tipoComercializacao)
    {
        $this->tipoComercializacao = $tipoComercializacao;
        return $this;
    }

    public function getCodigoBarrasBase()
    {
        return $this->codigoBarrasBase;
    }

    public function setCodigoBarrasBase($codigoBarrasBase)
    {
        $this->codigoBarrasBase = $codigoBarrasBase;
        return $this;
    }

    public function getReferencia()
    {
        return $this->referencia;
    }

    public function setReferencia($referencia)
    {
        $this->referencia = $referencia;
        return $this;
    }

    /**
     * Adicona um volume que compoe o produto
     * @param Volume $volume 
     */
    public function addVolume(Volume $volume)
    {
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
    public function getVolumes()
    {
        return $this->volumes;
    }

    /**
     * Adiciona uma embalagem que contem o produto
     * @param Embalagem $embalagem 
     */
    public function addEmbalagem(Embalagem $embalagem)
    {
        $embalagem->setProduto($this);
        $this->embalagens[] = $embalagem;
        return $this;
    }

    /**
     * 
     */
    public function getEmbalagens()
    {
        return $this->embalagens;
    }

    /**
     * Retorna a linha de separação a qual o produto pertence
     * @return LinhaSeparacao
     */
    public function getLinhaSeparacao()
    {
        return $this->linhaSeparacao;
    }

    /**
     * Informa a linha de separação a qual o produto pertence
     * @param LinhaSeparacao $linhaSeparacao 
     */
    public function setLinhaSeparacao(LinhaSeparacao $linhaSeparacao)
    {
        $this->linhaSeparacao = $linhaSeparacao;
        return $this;
    }

    public function getNumVolumes()
    {
        return $this->numVolumes;
    }

    public function setNumVolumes($numVolumes)
    {
        $this->numVolumes = $numVolumes;
        return $this;
    }

    /**
     * @return int
     */
    public function getDiasVidaUtil()
    {
        return $this->diasVidaUtil;
    }

    /**
     * @param int $diasVidaUtil
     */
    public function setDiasVidaUtil($diasVidaUtil)
    {
        $this->diasVidaUtil = $diasVidaUtil;
    }

    /**
     * @return string
     */
    public function getValidade()
    {
        return $this->validade;
    }

    /**
     * @param string $validade
     */
    public function setValidade($validade)
    {
        $this->validade = $validade;
    }

    /**
     * @param mixed $enderecoReferencia
     */
    public function setEnderecoReferencia($enderecoReferencia)
    {
        $this->enderecoReferencia = $enderecoReferencia;
    }

    /**
     * @return mixed
     */
    public function getEnderecoReferencia()
    {
        return $this->enderecoReferencia;
    }

}