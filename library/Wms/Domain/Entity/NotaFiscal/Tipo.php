<?php


namespace Wms\Domain\Entity\NotaFiscal;


/**
 * Class Tipo
 * @package Wms\Domain\Entity\NotaFiscal
 * @Table(name="TIPO_NOTA_ENTRADA")
 * @Entity(repositoryClass="Wms\Domain\Entity\NotaFiscal\TipoRepository")
 */
class Tipo
{
    const EMISSOR_CLIENTE = 'C';
    const EMISSOR_FORNECEDOR = 'F';

    public static $arrResponsaveis = [
        self::EMISSOR_CLIENTE => "Cliente",
        self::EMISSOR_FORNECEDOR => "Fornecedor",
    ];

    /**
     * @Id
     * @Column(name="COD_TIPO_NOTA_ENTRADA", type="integer", nullable=false)
     * @var integer
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_TIPO_NOTA_ENTRADA_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var string
     * @Column(name="DSC_TIPO_NOTA_ENTRADA", type="string", nullable=false)
     */
    protected $descricao;

    /**
     * @var string
     * @Column(name="IND_EMISSOR", type="string", length=1, nullable=false)
     */
    protected $emissor;

    /**
     * @var string
     * @Column(name="COD_EXTERNO", type="string", nullable=false)
     */
    protected $codExterno;

    /**
     * @var bool
     * @Column(name="SYS_DEFAULT", type="integer", nullable=false)
     */
    protected $systemDefault;

    public function __construct()
    {
        self::setEmissor(self::EMISSOR_FORNECEDOR);
        self::setSystemDefault(false);
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
     */
    public function setId($id)
    {
        $this->id = $id;
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
     */
    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
    }

    /**
     * @return string
     */
    public function getEmissor()
    {
        return $this->emissor;
    }

    /**
     * @param string $emissor
     */
    public function setEmissor($emissor)
    {
        $this->emissor = $emissor;
    }

    /**
     * @return string
     */
    public function getCodExterno()
    {
        return $this->codExterno;
    }

    /**
     * @param string $codExterno
     */
    public function setCodExterno($codExterno)
    {
        $this->codExterno = $codExterno;
    }

    /**
     * @return bool
     */
    public function isSystemDefault()
    {
        return empty($this->systemDefault);
    }

    /**
     * @param bool $systemDefault
     */
    public function setSystemDefault($systemDefault)
    {
        $this->systemDefault = intval($systemDefault);
    }
}