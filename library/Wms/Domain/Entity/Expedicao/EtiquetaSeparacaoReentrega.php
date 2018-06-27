<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="ETIQUETA_SEPARACAO_REENTREGA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoReentregaRepository")
 */
class EtiquetaSeparacaoReentrega
{
    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_ES_REENTREGA", type="bigint", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_ES_REENTREGA_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @Column(name="DTH_CONFERENCIA", type="datetime", nullable=true)
     */
    protected $dataConferencia;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_STATUS", referencedColumnName="COD_SIGLA")
     */
    protected $status;

    /**
     * @Column(name="COD_STATUS", type="integer", nullable=false)
     */
    protected $codStatus;

    /**
     * @Column(name="COD_OS_REENTREGA", type="integer", nullable=true)
     */
    protected $codOS;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\EtiquetaSeparacao")
     * @JoinColumn(name="COD_ETIQUETA_SEPARACAO", referencedColumnName="COD_ETIQUETA_SEPARACAO")
     */
    protected $etiquetaSeparacao;

    /**
     * @Column(name="COD_ETIQUETA_SEPARACAO", type="integer", nullable=false)
     */
    protected $codEtiquetaSeparacao;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\Reentrega")
     * @JoinColumn(name="COD_REENTREGA", referencedColumnName="COD_REENTREGA")
     */
    protected $reentrega;

    /**
     * @Column(name="COD_REENTREGA", type="integer", nullable=false)
     */
    protected $codReentrega;

    /**
     * @param mixed $codEtiquetaSeparacao
     */
    public function setCodEtiquetaSeparacao($codEtiquetaSeparacao)
    {
        $this->codEtiquetaSeparacao = $codEtiquetaSeparacao;
    }

    /**
     * @return mixed
     */
    public function getCodEtiquetaSeparacao()
    {
        return $this->codEtiquetaSeparacao;
    }

    /**
     * @param mixed $codOS
     */
    public function setCodOS($codOS)
    {
        $this->codOS = $codOS;
    }

    /**
     * @return mixed
     */
    public function getCodOS()
    {
        return $this->codOS;
    }

    /**
     * @param mixed $codReentrega
     */
    public function setCodReentrega($codReentrega)
    {
        $this->codReentrega = $codReentrega;
    }

    /**
     * @return mixed
     */
    public function getCodReentrega()
    {
        return $this->codReentrega;
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
    public function getCodStatus()
    {
        return $this->codStatus;
    }

    /**
     * @param mixed $dataConferencia
     */
    public function setDataConferencia($dataConferencia)
    {
        $this->dataConferencia = $dataConferencia;
    }

    /**
     * @return mixed
     */
    public function getDataConferencia()
    {
        return $this->dataConferencia;
    }

    /**
     * @param mixed $etiquetaSeparacao
     */
    public function setEtiquetaSeparacao($etiquetaSeparacao)
    {
        $this->etiquetaSeparacao = $etiquetaSeparacao;
    }

    /**
     * @return mixed
     */
    public function getEtiquetaSeparacao()
    {
        return $this->etiquetaSeparacao;
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $reentrega
     */
    public function setReentrega($reentrega)
    {
        $this->reentrega = $reentrega;
    }

    /**
     * @return mixed
     */
    public function getReentrega()
    {
        return $this->reentrega;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

}