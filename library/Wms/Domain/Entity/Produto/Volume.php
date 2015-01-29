<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\Common\Collections\ArrayCollection,
    Wms\Domain\Entity\Produto as ProdutoEntity,
    Wms\Domain\Entity\Produto\NormaPaletizacao as NormaPaletizacaoEntity,
    Core\Util\Converter;

/**
 * Description of Volume
 * @Table(name="PRODUTO_VOLUME")
 * @Entity(repositoryClass="Wms\Domain\Entity\Produto\VolumeRepository")
 * @author Renato Medina
 */
class Volume
{

    /**
     * @Column(name="COD_PRODUTO_VOLUME", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PRODUTO_VOLUME_01", allocationSize=1, initialValue=1)
     * @var integer
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     * @var Wms\Domain\Entity\Produto $produto Produto que o volumes está relacionado a
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
     * @Column(type="integer", name="COD_SEQUENCIAL_VOLUME")
     * @var integer código do volume
     */
    protected $codigoSequencial = 1;

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

    /**
     * @Column(name="DSC_VOLUME", type="string", length=255)
     */
    protected $descricao;

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
     * Norma de paletizacao do volume
     * 
     * @var Wms\Domain\Entity\Produto\NormaPaletizacao $normaPaletizacao
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\NormaPaletizacao", cascade={"persist"})
     * @JoinColumn(name="COD_NORMA_PALETIZACAO", referencedColumnName="COD_NORMA_PALETIZACAO")
     */
    protected $normaPaletizacao;

    /**
     * @Column(name="COD_BARRAS", type="string", length=60, nullable=false)
     * @var string código de barras da embalagem
     */
    protected $codigoBarras;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     * @var Wms\Domain\Entity\Deposito\Endereco $endereco
     */
    protected $endereco;


    /**
     * @Column(type="integer", name="COD_DEPOSITO_ENDERECO")
     * @var integer código do endereco
     */
    protected $codEndereco;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Recebimento\Volume", mappedBy="volume")
     * @var ArrayCollection lista de recebimentos desta embalagem
     */
    protected $recebimentoVolumes;

    public function __construct()
    {
        $this->recebimentoVolumes = new ArrayCollection;
    }

    /**
     * Retorna o código do volume
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Retorna o produto no qual este volume compõe
     * @return Produto
     */
    public function getProduto()
    {
        return $this->produto;
    }

    /**
     * Informa o produto no qual este volume compõe
     * @param Produto $produto 
     */
    public function setProduto(ProdutoEntity $produto)
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
     * Retorna o código sequencial do volume
     * @return integer
     */
    public function getCodigoSequencial()
    {
        return $this->codigoSequencial;
    }

    /**
     * Informa o código sequencial do volume
     * @param integer $codigoSequencial 
     */
    public function setCodigoSequencial($codigoSequencial)
    {
        $this->codigoSequencial = $codigoSequencial;
        return $this;
    }

    /**
     * Retorna a norma de paletizacao
     * @return integer
     */
    public function getNormaPaletizacao()
    {
        return $this->normaPaletizacao;
    }

    /**
     * Registra a norma de paletizacao
     * @param integer $normaPaletizacaoEntity
     */
    public function setNormaPaletizacao(NormaPaletizacaoEntity $normaPaletizacaoEntity)
    {
        $this->normaPaletizacao = $normaPaletizacaoEntity;
        return $this;
    }

    /**
     * Retorna a altura do produto
     * @return decimal
     */
    public function getAltura()
    {
        return Converter::enToBr($this->altura, 3);
    }

    /**
     * Informa a altura do volume
     * @param decimal $altura 
     */
    public function setAltura($altura)
    {
        $this->altura = Converter::brToEn($altura, 3);
        return $this;
    }

    /**
     * Retorna a largura do volume
     * @return decimal
     */
    public function getLargura()
    {
        return Converter::enToBr($this->largura, 3);
    }

    /**
     * Informa a largura do volume
     * @param decimal $largura 
     */
    public function setLargura($largura)
    {
        $this->largura = Converter::brToEn($largura, 3);
        return $this;
    }

    /**
     * Retorna a profundidade do volume
     * @return decimal
     */
    public function getProfundidade()
    {
        return Converter::enToBr($this->profundidade, 3);
    }

    /**
     * Informa a profundidade do volume
     * @param decimal $profundidade 
     */
    public function setProfundidade($profundidade)
    {
        $this->profundidade = Converter::brToEn($profundidade, 3);
        return $this;
    }

    /**
     * Retorna a cubagem do volume
     * @return decimal
     */
    public function getCubagem()
    {
        return Converter::enToBr($this->cubagem, 4);
    }

    /**
     * Informa a cubagem do volume
     * @param decimal $cubagem 
     */
    public function setCubagem($cubagem)
    {
        $this->cubagem = Converter::brToEn($cubagem, 4);
        return $this;
    }

    /**
     * Retorna o peso do volume
     * @return decimal
     */
    public function getPeso()
    {
        return Converter::enToBr($this->peso, 3);
    }

    /**
     * Informa o peso do volume
     * @param decimal $peso 
     */
    public function setPeso($peso)
    {
        $this->peso = Converter::brToEn($peso, 3);
        return $this;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
        return $this;
    }

    /**
     * Retorna o código de barras do volume
     * @return string
     */
    public function getCodigoBarras()
    {
        return $this->codigoBarras;
    }

    /**
     * Informa o código de barras do volume
     * @param string $codigoBarras 
     */
    public function setCodigoBarras($codigoBarras)
    {
        $this->codigoBarras = $codigoBarras;
        return $this;
    }

    public function getRecebimentoVolumes()
    {
        return $this->recebimentoVolumes;
    }

    public function setRecebimentoVolumes($recebimentoVolumes)
    {
        $this->recebimentoVolumes = $recebimentoVolumes;
        return $this;
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

}
