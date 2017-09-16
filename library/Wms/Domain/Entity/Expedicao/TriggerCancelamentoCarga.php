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

}