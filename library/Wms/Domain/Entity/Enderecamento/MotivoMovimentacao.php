<?php


namespace Wms\Domain\Entity\Enderecamento;

use Wms\Domain\Configurator;
use Wms\Domain\Entity\Usuario;

/**
 * Class MotivoMovimentacao
 * @package Wms\Domain\Entity\Enderecamento
 *
 * @Table(name="MOTIVO_MOVIMENTACAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\MotivoMovimentacaoRepository")
 */
class MotivoMovimentacao
{
    /**
     * @var int
     * @Column(name="COD_MOTIVO_MOVIMENTACAO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_MOTIVO_MOVIMENTACAO_01", allocationSize=1, initialValue=2)
     */
    protected $id;

    /**
     * @var string
     * @Column(name="DSC_MOTIVO_MOVIMENTACAO", type="string", nullable=false, length=200)
     */
    protected $descricao;

    /**
     * @var string
     * @Column(name="COD_EXTERNO", type="string", nullable=true, length=10)
     */
    protected $codExterno;

    /**
     * @var \DateTime
     * @Column(name="DTH_CRIACAO", type="datetime", nullable=false)
     */
    protected $dthCriacao;

    /**
     * @var Usuario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO_CRIACAO", referencedColumnName="COD_USUARIO")
     */
    protected $usuarioCriacao;

    /**
     * @var bool
     * @Column(name="IND_ATIVO", type="boolean", nullable=false)
     */
    protected $isAtivo;

    public function __construct()
    {
        self::setIsAtivo(true);
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
     * @param $toString boolean Converter para String a data
     * @return \DateTime | string
     */
    public function getDthCriacao($toString = false)
    {
        return ($toString && !empty($this->dthCriacao)) ? $this->dthCriacao->format('d/m/Y H:i:s') : $this->dthCriacao ;
    }

    /**
     * @return Usuario
     */
    public function getUsuarioCriacao()
    {
        return $this->usuarioCriacao;
    }

    /**
     * @param Usuario $usuarioCriacao
     */
    public function setUsuarioCriacao($usuarioCriacao)
    {
        $this->usuarioCriacao = $usuarioCriacao;
    }

    /**
     * @return bool
     */
    public function isAtivo()
    {
        return ($this->isAtivo);
    }

    /**
     * @param bool $isAtivo
     */
    public function setIsAtivo($isAtivo)
    {
        $this->isAtivo = $isAtivo;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function toArray()
    {
        return Configurator::configureToArray($this);
    }
}