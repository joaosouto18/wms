<?php

namespace Wms\Domain\Entity;
use Wms\Domain\Entity\Expedicao\ModeloSeparacao;
use Wms\Domain\Entity\Util\Sigla;

/**
 * Expedição
 *
 * @Table(name="EXPEDICAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\ExpedicaoRepository")
 */
class Expedicao
{

    const STATUS_INTEGRADO = 462;
    const STATUS_EM_SEPARACAO = 463;
    const STATUS_EM_CONFERENCIA = 464;
    const STATUS_FINALIZADO = 465;
    const STATUS_CANCELADO = 466;
    const STATUS_PARCIALMENTE_FINALIZADO = 530;
    const STATUS_PRIMEIRA_CONFERENCIA = 551;
    const STATUS_SEGUNDA_CONFERENCIA = 552;
    const STATUS_EM_FINALIZACAO = 622;
    /**
     * @Column(name="COD_EXPEDICAO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_EXPEDICAO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DSC_PLACA_EXPEDICAO", type="string", length=10, nullable=false)
     */
    protected $placaExpedicao;

    /**
     * @Column(name="CENTRAL_PF", type="integer",  nullable=true)
     */
    protected $centralEntregaParcFinalizada;

    /**
     * @Column(name="COD_STATUS", type="integer",  nullable=true)
     */
    protected $codStatus;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_STATUS", referencedColumnName="COD_SIGLA")
     */
    protected $status;

    /**
     * Data e hora iniciou a expedição
     * 
     * @Column(name="DTH_INICIO", type="datetime", nullable=false)
     */
    protected $dataInicio;

    /**
     * Data e hora que finalizou a expedição
     *
     * @Column(name="DTH_FINALIZACAO", type="datetime", nullable=true)
     */
    protected $dataFinalizacao;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Box")
     * @JoinColumn(name="COD_BOX", referencedColumnName="COD_BOX")
     */
    protected $box;

    /**
     * @Column(name="COD_DEPOSITO", type="integer", nullable=false)
     */
    protected $codDeposito;

    /**
     * @column(name="TIPO_FECHAMENTO", type="string", length=1, nullable=false)
     */
    protected $tipoFechamento;
	
	/**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Expedicao\Carga", mappedBy="expedicao")
     */
    protected $carga;

    /**
     * @var string
     * @Column(name="IND_PROCESSANDO", type="string")
     */
    protected $indProcessando;

    /**
     * @var ModeloSeparacao
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\ModeloSeparacao")
     * @JoinColumn(name="COD_MODELO_SEPARACAO", referencedColumnName="COD_MODELO_SEPARACAO")
     */
    protected $modeloSeparacao;

    /**
     * @var int
     *
     * @Column(name="COUNT_VOLUMES", type="integer", nullable=true)
     */
    protected $countVolumes;

    /**
     * @var string
     * @Column(name="IND_HABILITADO_CORTE_ERP", type="string")
     */
    protected $corteERPHabilitado;

	public function setCarga($carga)
    {
        $this->carga = $carga;
    }

    public function getCarga()
    {
        return $this->carga;
    }
	
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return Sigla
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function setBox($box)
    {
        $this->box = $box;
    }

    public function getBox()
    {
        return $this->box;
    }

    public function setCodDeposito($codDeposito)
    {
        $this->codDeposito = $codDeposito;
    }

    public function getCodDeposito()
    {
        return $this->codDeposito;
    }

    public function setDataFinalizacao($dataFinalizacao)
    {
        $this->dataFinalizacao = $dataFinalizacao;
    }

    public function getDataFinalizacao()
    {
        return $this->dataFinalizacao;
    }

    public function setDataInicio($dataInicio)
    {
        $this->dataInicio = $dataInicio;
    }

    public function getDataInicio()
    {
        return $this->dataInicio;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setPlacaExpedicao($placaExpedicao)
    {
        $this->placaExpedicao = $placaExpedicao;
    }

    public function getPlacaExpedicao()
    {
        return $this->placaExpedicao;
    }

    public function setCentralEntregaParcFinalizada($centralEntregaParcFinalizada)
    {
        $this->centralEntregaParcFinalizada = $centralEntregaParcFinalizada;
    }

    public function getCentralEntregaParcFinalizada()
    {
        return $this->centralEntregaParcFinalizada;
    }

    /**
     * @return mixed
     */
    public function getTipoFechamento()
    {
        return $this->tipoFechamento;
    }

    /**
     * @param mixed $tipoFechamento
     */
    public function setTipoFechamento($tipoFechamento)
    {
        $this->tipoFechamento = $tipoFechamento;
    }

    /**
     * @return mixed
     */
    public function getCodStatus()
    {
        return $this->codStatus;
    }

    /**
     * @param mixed $codStatus
     */
    public function setCodStatus($codStatus)
    {
        $this->codStatus = $codStatus;
    }

    /**
     * @return string
     */
    public function getIndProcessando()
    {
        return $this->indProcessando;
    }

    /**
     * @param string $indProcessando
     */
    public function setIndProcessando($indProcessando)
    {
        $this->indProcessando = $indProcessando;
    }

    /**
     * @return ModeloSeparacao
     */
    public function getModeloSeparacao()
    {
        return $this->modeloSeparacao;
    }

    /**
     * @param ModeloSeparacao $modeloSeparacao
     */
    public function setModeloSeparacao($modeloSeparacao)
    {
        $this->modeloSeparacao = $modeloSeparacao;
    }

    /**
     * @return int
     */
    public function getCountVolumes()
    {
        return $this->countVolumes;
    }

    /**
     * @param int $countVolumes
     * @return Expedicao
     */
    public function setCountVolumes($countVolumes)
    {
        $this->countVolumes = $countVolumes;
        return $this;
    }

    /**
     * @return string
     */
    public function getCorteERPHabilitado()
    {
        return $this->corteERPHabilitado;
    }

    /**
     * @param string $corteERPHabilitado
     */
    public function setCorteERPHabilitado($corteERPHabilitado)
    {
        $this->corteERPHabilitado = $corteERPHabilitado;
    }

}