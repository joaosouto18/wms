<?php


namespace Wms\Domain\Entity\NotaFiscal;


/**
 * Class Tipo
 * @package Wms\Domain\Entity\NotaFiscal
 * @Table(name="TIPO_NOTA_FISCAL")
 * @Entity(repositoryClass="Wms\Domain\Entity\NotaFiscal\TipoRepository")
 */
class Tipo
{
    const RESPONSAVEL_CLIENTE = 'C';
    const RESPONSAVEL_FORNECEDOR = 'F';

    public static $arrResponsaveis = [
        self::RESPONSAVEL_CLIENTE => "Cliente",
        self::RESPONSAVEL_FORNECEDOR => "Fornecedor",
    ];

    /**
     * @Id
     * @Column(name="COD_TIPO", type="integer", nullable=false)
     * @var integer
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_TIPO_NOTA_FISCAL_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var string
     * @Column(name="DSC_TIPO", type="string", nullable=false)
     */
    protected $descricao;

    /**
     * @var string
     * @Column(name="IND_TIPO_RESPONSAVEL", type="string", length=1, nullable=false)
     */
    protected $tipoResponsavel;

    /**
     * @var string
     * @Column(name="COD_EXTERNO", type="string", nullable=false)
     */
    protected $codErp;

    public function __construct()
    {
        self::setTipoResponsavel(self::RESPONSAVEL_FORNECEDOR);
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
    public function getTipoResponsavel()
    {
        return $this->tipoResponsavel;
    }

    /**
     * @param string $tipoResponsavel
     */
    public function setTipoResponsavel($tipoResponsavel)
    {
        $this->tipoResponsavel = $tipoResponsavel;
    }

    /**
     * @return string
     */
    public function getCodErp()
    {
        return $this->codErp;
    }

    /**
     * @param string $codErp
     */
    public function setCodErp($codErp)
    {
        $this->codErp = $codErp;
    }
}