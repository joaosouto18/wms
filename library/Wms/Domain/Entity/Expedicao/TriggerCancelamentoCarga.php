<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 * Carga
 *
 * @Table(name="TR_CANCELAMENTO_CARGA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\TriggerCancelamentoCargaRepository")
 */
class TriggerCancelamentoCarga
{

    /**
     * @Column(name="COD_CARGA_EXTERNO", type="integer", nullable=false)
     * @Id
     */
    protected $codCargaExterno;
    

    /**
     * @Column(name="DTH_CANCELAMENTO", type="datetime",nullable=false)
     * @var datetime
     */
    protected $dataCancelamento;

    /**
     * @OneToOnetargetEntity="Wms\Domain\Entity\Expedicao\Carga")
     * @JoinColumn(name="COD_CARGA_EXTERNO", referencedColumnName="COD_CARGA_EXTERNO")
     */
    protected $carga;

    /**
     * @Column(name="IND_PROCESSADO", type="string")
     */
    protected $processado;

    /**
     * @Column(name="DTH_INCLUSAO", type="datetime")
     * @var datetime
     */
    protected $dataInclusao;

    /**
     * @Column(name="DTH_PROCESSAMENTO", type="datetime")
     * @var datetime
     */
    protected $dataProcessamento;

    /**
     * @Column(name="ID", type="integer")
     */
    protected $id;

    /**
     * @return int
     */
    public function getCodCargaExterno()
    {
        return $this->codCargaExterno;
    }

    /**
     * @param int $codCargaExterno
     */
    public function setCodCargaExterno($codCargaExterno)
    {
        $this->codCargaExterno = $codCargaExterno;
    }

    /**
     * @return datetime
     */
    public function getDataCancelamento()
    {
        return $this->dataCancelamento;
    }

    /**
     * @param datetime $dataCancelamento
     */
    public function setDataCancelamento($dataCancelamento)
    {
        $this->dataCancelamento = $dataCancelamento;
    }

    /**
     * @return mixed
     */
    public function getCarga()
    {
        return $this->carga;
    }

    /**
     * @param mixed $carga
     */
    public function setCarga($carga)
    {
        $this->carga = $carga;
    }

    /**
     * @return mixed
     */
    public function getProcessado()
    {
        return $this->processado;
    }

    /**
     * @param mixed $processado
     */
    public function setProcessado($processado)
    {
        $this->processado = $processado;
    }

    /**
     * @return datetime
     */
    public function getDataInclusao()
    {
        return $this->dataInclusao;
    }

    /**
     * @param datetime $dataInclusao
     */
    public function setDataInclusao($dataInclusao)
    {
        $this->dataInclusao = $dataInclusao;
    }

    /**
     * @return datetime
     */
    public function getDataProcessamento()
    {
        return $this->dataProcessamento;
    }

    /**
     * @param datetime $dataProcessamento
     */
    public function setDataProcessamento($dataProcessamento)
    {
        $this->dataProcessamento = $dataProcessamento;
    }

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

}