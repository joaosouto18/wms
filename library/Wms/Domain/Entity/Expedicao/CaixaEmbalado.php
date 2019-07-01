<?php


namespace Wms\Domain\Entity\Expedicao;

/**
 * Class CaixaEmbalado
 * @package Wms\Domain\Entity\Expedicao
 *
 * @Table(name="CAIXA_EMBALADO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\CaixaEmbaladoRepository")
 */
class CaixaEmbalado
{

    /**
     * @var integer
     *
     * @Column(name="COD_CAIXA", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_CAIXA_EMB_01", allocationSize=1, initialValue=1)
     * @GeneratedValue(strategy="SEQUENCE")
     * @Id
     */
    protected $id;

    /**
     * @var string
     *
     * @Column(name="DSC_CAIXA", type="string", nullable=false)
     */
    protected $descricao;

    /**
     * @var float
     *
     * @Column(name="PESO_MAX", type="float", nullable=false)
     */
    protected $pesoMaximo;

    /**
     * @var float
     *
     * @Column(name="CUBAGEM_MAX", type="float", nullable=false)
     */
    protected $cubagemMaxima;

    /**
     * @var integer
     *
     * @Column(name="MIX_MAX", type="integer", nullable=false)
     */
    protected $mixMaximo;

    /**
     * @var integer
     *
     * @Column(name="UNIDADES_MAX", type="integer", nullable=false)
     */
    protected $unidadesMaxima;

    /**
     * @var bool
     *
     * @Column(name="IS_ATIVA", type="bool", nullable=false)
     */
    protected $isAtiva;

    /**
     * @var bool
     *
     * @Column(name="IS_DEFAULT", type="bool", nullable=false)
     */
    protected $isDefault;

    public function __construct()
    {
        self::setPesoMaximo(0.0);
        self::setCubagemMaxima(0.0);
        self::setMixMaximo(0);
        self::setUnidadesMaxima(0);
        self::setUnidadesMaxima(0);
        self::setIsAtiva(true);
        self::setIsDefault(true);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return CaixaEmbalado
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescricao()
    {
        return $this->descricao;
    }

    /**
     * @param string $descricao
     * @return CaixaEmbalado
     */
    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
        return $this;
    }

    /**
     * @return float
     */
    public function getPesoMaximo()
    {
        return $this->pesoMaximo;
    }

    /**
     * @param float $pesoMaximo
     * @return CaixaEmbalado
     */
    public function setPesoMaximo($pesoMaximo)
    {
        $this->pesoMaximo = $pesoMaximo;
        return $this;
    }

    /**
     * @return float
     */
    public function getCubagemMaxima()
    {
        return $this->cubagemMaxima;
    }

    /**
     * @param float $cubagemMaxima
     * @return CaixaEmbalado
     */
    public function setCubagemMaxima($cubagemMaxima)
    {
        $this->cubagemMaxima = $cubagemMaxima;
        return $this;
    }

    /**
     * @return int
     */
    public function getMixMaximo()
    {
        return $this->mixMaximo;
    }

    /**
     * @param int $mixMaximo
     * @return CaixaEmbalado
     */
    public function setMixMaximo($mixMaximo)
    {
        $this->mixMaximo = $mixMaximo;
        return $this;
    }

    /**
     * @return int
     */
    public function getUnidadesMaxima()
    {
        return $this->unidadesMaxima;
    }

    /**
     * @param int $unidadesMaxima
     * @return CaixaEmbalado
     */
    public function setUnidadesMaxima($unidadesMaxima)
    {
        $this->unidadesMaxima = $unidadesMaxima;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAtiva()
    {
        return $this->isAtiva;
    }

    /**
     * @param bool $isAtiva
     * @return CaixaEmbalado
     */
    public function setIsAtiva($isAtiva)
    {
        $this->isAtiva = $isAtiva;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param bool $isDefault
     * @return CaixaEmbalado
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;
        return $this;
    }
}