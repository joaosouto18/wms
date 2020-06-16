<?php

namespace Wms\Domain\Entity\Expedicao;

use Wms\Domain\Configurator;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\OrdemServico;
use Wms\Domain\Entity\Pessoa\Papel\Cliente;
use Wms\Domain\Entity\Usuario;

/**
 * @Table(name="CONFERENCIA_CARREGAMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\ConferenciaCarregamentoRepository")
 */
class ConferenciaCarregamento
{
    const STATUS_GERADO = 0;
    const STATUS_EM_ANDAMENTO = 1;
    const STATUS_FINALIZADO = 2;

    /**
     * @var int
     * @Column(name="COD_CONF_CARREG", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_CONF_CARREG_01", allocationSize=1, initialValue=1)
     * @GeneratedValue(strategy="SEQUENCE")
     * @Id
     */
    protected $id;

    /**
     * @var Expedicao
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao")
     * @JoinColumn(name="COD_EXPEDICAO", referencedColumnName="COD_EXPEDICAO")
     */
    protected $expedicao;

    /**
     * @var string
     * @Column(name="TIPO_CONF_CARREG", type="string", nullable=false)
     */
    protected $tipoConferencia;

    /**
     * @var integer
     * @Column(name="COD_STATUS", type="integer", nullable=false)
     */
    protected $status;

    /**
     * @var Usuario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuarioAbertura;

    /**
     * @var \DateTime
     * @Column(name="DTH_INICIO", type="datetime", nullable=false)
     */
    protected $dthInicio;

    /**
     * @var \DateTime
     * @Column(name="DTH_FIM", type="datetime")
     */
    protected $dthFim;

    public function __construct()
    {
        $this->dthInicio = new \DateTime();
        $this->status = self::STATUS_GERADO;
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
     * @return Expedicao
     */
    public function getExpedicao()
    {
        return $this->expedicao;
    }

    /**
     * @param Expedicao $expedicao
     */
    public function setExpedicao($expedicao)
    {
        $this->expedicao = $expedicao;
    }

    /**
     * @return string
     */
    public function getTipoConferencia()
    {
        return $this->tipoConferencia;
    }

    /**
     * @param string $tipoConferencia
     */
    public function setTipoConferencia($tipoConferencia)
    {
        $this->tipoConferencia = $tipoConferencia;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isGerado()
    {
        return ($this->status == self::STATUS_GERADO);
    }

    /**
     * @return bool
     */
    public function isEmAndamento()
    {
        return ($this->status == self::STATUS_EM_ANDAMENTO);
    }

    /**
     * @return bool
     */
    public function isFinalizada()
    {
        return ($this->status == self::STATUS_FINALIZADO);
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return Usuario
     */
    public function getUsuarioAbertura()
    {
        return $this->usuarioAbertura;
    }

    /**
     * @param Usuario $usuarioAbertura
     */
    public function setUsuarioAbertura($usuarioAbertura)
    {
        $this->usuarioAbertura = $usuarioAbertura;
    }

    /**
     * @return \DateTime
     */
    public function getDthInicio()
    {
        return $this->dthInicio;
    }

    /**
     * @param \DateTime $dthInicio
     */
    public function setDthInicio($dthInicio)
    {
        $this->dthInicio = $dthInicio;
    }

    /**
     * @return \DateTime
     */
    public function getDthFim()
    {
        return $this->dthFim;
    }

    /**
     * @param \DateTime $dthFim
     */
    public function setDthFim($dthFim)
    {
        $this->dthFim = $dthFim;
    }

    public function toArray()
    {
        return Configurator::configureToArray($this);
    }
}