<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\Common\Collections\ArrayCollection,
    Wms\Domain\Entity\Produto as ProdutoEntity,
    Wms\Domain\Entity\Produto\NormaPaletizacao as NormaPaletizacaoEntity,
    Core\Util\Converter;
use Wms\Domain\Entity\Produto;

use Wms\Domain\Entity\Deposito\Endereco;
$andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');

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
     * @var \Wms\Domain\Entity\Produto $produto Produto que o volumes está relacionado a
     */
    protected $produto;

    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     * @var string Código do produto
     */
    protected $codProduto;

    /**
     * @var string Grade do produto
     * @Column(name="DSC_GRADE", type="string", length=255, nullable=false)
     */
    protected $grade;

    /**
     * @Column(type="integer", name="COD_SEQUENCIAL_VOLUME")
     * @var integer código do volume
     */
    protected $codigoSequencial = 1;

    /**
     * @Column(type="decimal", name="NUM_ALTURA")
     * @var float altura do volume
     */
    protected $altura;

    /**
     * @Column(type="decimal", name="NUM_LARGURA")
     * @var float largura do volume
     */
    protected $largura;

    /**
     * @Column(type="decimal", name="NUM_PROFUNDIDADE")
     * @var float profundidade do volume
     */
    protected $profundidade;

    /**
     * @Column(type="decimal", name="NUM_CUBAGEM")
     * @var float cubagem do volume
     */
    protected $cubagem;

    /**
     * @Column(type="decimal", name="NUM_PESO")
     * @var float peso do volume
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
     * @var \Wms\Domain\Entity\Produto\NormaPaletizacao $normaPaletizacao
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
     * @var \Wms\Domain\Entity\Deposito\Endereco $endereco
     */
    protected $endereco;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Recebimento\Volume", mappedBy="volume")
     * @var ArrayCollection lista de recebimentos desta embalagem
     */
    protected $recebimentoVolumes;

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
     * @var \datetime
     */
    protected $dataInativacao;

    /**
     * @Column(name="COD_USUARIO_INATIVACAO", type="integer", nullable=false)
     * @var int
     */
    protected $usuarioInativacao;

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
     * @return Volume
     */
    public function setProduto(ProdutoEntity $produto)
    {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Produto', $this->produto, $produto);
        $this->produto = $produto;
        return $this;
    }

    public function getGrade()
    {
        return $this->grade;
    }

    public function setGrade($grade)
    {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Grade', $this->grade, $grade);
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
     * @return Volume
     */
    public function setCodigoSequencial($codigoSequencial)
    {
        $this->codigoSequencial = $codigoSequencial;
        return $this;
    }

    /**
     * Retorna a norma de paletizacao
     * @return NormaPaletizacaoEntity
     */
    public function getNormaPaletizacao()
    {
        return $this->normaPaletizacao;
    }

    /**
     * Registra a norma de paletizacao
     * @param integer|NormaPaletizacaoEntity $normaPaletizacaoEntity
     * @return Volume
     */
    public function setNormaPaletizacao(NormaPaletizacaoEntity $normaPaletizacaoEntity)
    {
        $this->normaPaletizacao = $normaPaletizacaoEntity;
        return $this;
    }

    /**
     * Retorna a altura do produto
     * @return float
     */
    public function getAltura()
    {
        return Converter::enToBr($this->altura, 3);
    }

    /**
     * Informa a altura do volume
     * @param float $altura
     */
    public function setAltura($altura)
    {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Altura', number_format($this->altura, 3, ',', ''), $altura);
        $this->altura = Converter::brToEn($altura, 3);
        return $this;
    }

    /**
     * Retorna a largura do volume
     * @return float
     */
    public function getLargura()
    {
        return Converter::enToBr($this->largura, 3);
    }

    /**
     * Informa a largura do volume
     * @param float $largura
     * @return Volume
     */
    public function setLargura($largura)
    {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Largura', number_format($this->largura, 3, ',', ''), $largura);
        $this->largura = Converter::brToEn($largura, 3);
        return $this;
    }

    /**
     * Retorna a profundidade do volume
     * @return float
     */
    public function getProfundidade()
    {
        return Converter::enToBr($this->profundidade, 3);
    }

    /**
     * Informa a profundidade do volume
     * @param float $profundidade
     * @return Volume
     */
    public function setProfundidade($profundidade)
    {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Profundidade',  number_format($this->profundidade, 3, ',', ''), $profundidade);
        $this->profundidade = Converter::brToEn($profundidade, 3);
        return $this;
    }

    /**
     * Retorna a cubagem do volume
     * @return float
     */
    public function getCubagem()
    {
        return Converter::enToBr($this->cubagem, 4);
    }

    /**
     * Informa a cubagem do volume
     * @param float $cubagem
     * @return Volume
     */
    public function setCubagem($cubagem)
    {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Cubagem', number_format($this->cubagem, 4, ',', ''), $cubagem);
        $this->cubagem = Converter::brToEn($cubagem, 4);
        return $this;
    }

    /**
     * Retorna o peso do volume
     * @return float
     */
    public function getPeso()
    {
        return Converter::enToBr($this->peso, 3);
    }

    /**
     * Informa o peso do volume
     * @param float $peso
     * @return Volume
     */
    public function setPeso($peso, $importacao = null)
    {
        if (empty($importacao)) {
            $this->peso = Converter::brToEn($peso, 3);
        } else {
            $this->peso = $peso;
        }
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Peso', $this->peso, $peso);
        return $this;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setDescricao($descricao)
    {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Descrição Embalagem', $this->descricao, $descricao);
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
     * @return Volume
     */
    public function setCodigoBarras($codigoBarras)
    {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Código de Barras', $this->codigoBarras, $codigoBarras);
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

    /**
     * @return \Wms\Domain\Entity\Deposito\Endereco
     */
    public function getEndereco()
    {
        return $this->endereco;
    }

    public function setEndereco($endereco)
    {

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

    public function getCBInterno()
    {
        return $this->CBInterno;
    }

    public function setCBInterno($CBInterno)
    {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Código de barras interno', $this->CBInterno, $CBInterno);
        $this->CBInterno = $CBInterno;
        return $this;
    }

    public function getImprimirCB()
    {
        return $this->imprimirCB;
    }

    public function setImprimirCB($imprimirCB)
    {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Imprimir código de barras interno', $this->imprimirCB, $imprimirCB);
        $this->imprimirCB = $imprimirCB;
        return $this;
    }

    /**
     * @param mixed $capacidadePicking
     */
    public function setCapacidadePicking($capacidadePicking)
    {
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Capacidade de Picking', $this->capacidadePicking, $capacidadePicking);

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
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Ponto de Reposição', $this->pontoReposicao, $pontoReposicao);
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
        $andamentoRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository('wms:Produto\Andamento');
        $andamentoRepo->checksChange($this->getProduto(), 'Código do Produto', $this->codProduto, $codProduto);
        $this->codProduto = $codProduto;
    }

    /**
     * @return \datetime
     */
    public function getDataInativacao()
    {
        return $this->dataInativacao;
    }

    /**
     * @param \datetime $dataInativacao
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

}
