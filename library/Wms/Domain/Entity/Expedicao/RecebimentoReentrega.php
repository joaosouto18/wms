<?php

namespace Wms\Domain\Entity\Expedicao;
use Zend\Stdlib\Configurator;

/**
 *
 * @Table(name="RECEBIMENTO_REENTREGA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\RecebimentoReentregaRepository")
 */
class RecebimentoReentrega
{
    const RECEBIMENTO_INICIADO = 559;
    const RECEBIMENTO_CANCELADO = 560;
    const RECEBIMENTO_CONCLUIDO = 561;


    /**
     * @Id
     * @Column(name="COD_RECEBIMENTO_REENTREGA", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RECEBIMENTO_REENTREGA_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_STATUS", referencedColumnName="COD_SIGLA")
     */
    protected $status;

    /**
     * @Column(name="DTH_CRIACAO", type="date")
     * @var string
     */
    protected $dataCriacao;

    /**
     * @Column(name="OBSERVACAO", type="string")
     * @var string
     */
    protected $observacao;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * @Column(name="NUM_CONFERENCIA", type="integer")
     * $var int
     */
    protected $numeroConferencia;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getDataCriacao()
    {
        return $this->dataCriacao;
    }

    /**
     * @param string $dataCriacao
     */
    public function setDataCriacao($dataCriacao)
    {
        $this->dataCriacao = $dataCriacao;
    }

    /**
     * @return string
     */
    public function getObservacao()
    {
        return $this->observacao;
    }

    /**
     * @param string $observacao
     */
    public function setObservacao($observacao)
    {
        $this->observacao = $observacao;
    }

    /**
     * @return mixed
     */
    public function getUsuario()
    {
        return $this->usuario;
    }

    /**
     * @param mixed $usuario
     */
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
    }

    /**
     * @return mixed
     */
    public function getNumeroConferencia()
    {
        return $this->numeroConferencia;
    }

    /**
     * @param mixed $numeroConferencia
     */
    public function setNumeroConferencia($numeroConferencia)
    {
        $this->numeroConferencia = $numeroConferencia;
    }

}