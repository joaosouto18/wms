<?php

namespace Wms\Domain\Entity;

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

}