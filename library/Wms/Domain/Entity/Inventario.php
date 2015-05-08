<?php

namespace Wms\Domain\Entity;

/**
 * @Table(name="INVENTARIO")
 * @Entity(repositoryClass="Wms\Domain\Entity\InventarioRepository")
 */
class Inventario
{

    const STATUS_GERADO = 542;
    const STATUS_LIBERADO = 543;
    const STATUS_FINALIZADO = 544;
    const STATUS_CANCELADO = 545;

    /**
     * @Column(name="COD_INVENTARIO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_INVENTARIO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

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
     * @Column(name="DTH_INICIO", type="datetime", nullable=false)
     */
    protected $dataInicio;

    /**
     * @Column(name="DTH_FINALIZACAO", type="datetime", nullable=true)
     */
    protected $dataFinalizacao;

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
     * @return mixed
     */
    public function getDataFinalizacao()
    {
        return $this->dataFinalizacao;
    }

    /**
     * @param mixed $dataFinalizacao
     */
    public function setDataFinalizacao($dataFinalizacao)
    {
        $this->dataFinalizacao = $dataFinalizacao;
    }

    /**
     * @return mixed
     */
    public function getDataInicio()
    {
        return $this->dataInicio;
    }

    /**
     * @param mixed $dataInicio
     */
    public function setDataInicio($dataInicio)
    {
        $this->dataInicio = $dataInicio;
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



}