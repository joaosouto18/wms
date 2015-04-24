<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 * Carga
 *
 * @Table(name="CARGA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\CargaRepository")
 */
class Carga
{
    /**
     * @Column(name="COD_CARGA", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_CARGA_01", allocationSize=1, initialValue=1)
     * @GeneratedValue(strategy="SEQUENCE")
     * @Id
     */
    protected $id;

    /**
     * @Column(name="COD_CARGA_EXTERNO", type="integer", nullable=false)
     */
    protected $codCargaExterno;
    
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_TIPO_CARGA", referencedColumnName="COD_SIGLA")
     */
    protected $tipoCarga;

    /**
     * Central de Entrega equivale ao código do CD
     * @Column(name="CENTRAL_ENTREGA", type="integer", nullable=false)
     */
    protected $centralEntrega;

    /**
     * @Column(name="DSC_PLACA_CARGA", type="string",nullable=false)
     */
    protected $placaCarga;

    /**
     * @Column(name="DSC_PLACA_EXPEDICAO", type="string",nullable=false)
     */
    protected $placaExpedicao;

    /**
     * Data e hora que liberou a carga no Erp indicando que não terá mais alterações na mesma
     *
     * @Column(name="DTH_FECHAMENTO", type="datetime", nullable=true)
     */
    protected $dataFechamento;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao")
     * @JoinColumn(name="COD_EXPEDICAO", referencedColumnName="COD_EXPEDICAO")
     */
    protected $expedicao;

    /**
     * @Column(name="COD_EXPEDICAO", type="integer")
     */
    protected $codExpedicao;

    /**
     * @Column(name="SEQUENCIA", type="integer",nullable=false)
     */
    protected $sequencia;

    public function setCentralEntrega($centralEntrega)
    {
        $this->centralEntrega = $centralEntrega;
    }

    public function getCentralEntrega()
    {
        return $this->centralEntrega;
    }

    public function setCodCargaExterno($codCargaExterno)
    {
        $this->codCargaExterno = $codCargaExterno;
    }

    public function getCodCargaExterno()
    {
        return $this->codCargaExterno;
    }

    public function setDataFechamento($dataFechamento)
    {
        $this->dataFechamento = $dataFechamento;
    }

    public function getDataFechamento()
    {
        return $this->dataFechamento;
    }

    public function setExpedicao($expedicao)
    {
        $this->expedicao = $expedicao;
    }

    /**
     * @return \Wms\Domain\Entity\Expedicao
     */
    public function getExpedicao()
    {
        return $this->expedicao;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setPlacaCarga($placaCarga)
    {
        $this->placaCarga = $placaCarga;
    }

    public function getPlacaCarga()
    {
        return $this->placaCarga;
    }

    public function setPlacaExpedicao($placaExpedicao)
    {
        $this->placaExpedicao = $placaExpedicao;
    }

    public function getPlacaExpedicao()
    {
        return $this->placaExpedicao;
    }

    public function setTipoCarga($tipoCarga)
    {
        $this->tipoCarga = $tipoCarga;
    }

    public function getTipoCarga()
    {
        return $this->tipoCarga;
    }

    public function setCodExpedicao($codExpedicao)
    {
        $this->codExpedicao = $codExpedicao;
    }

    public function getCodExpedicao()
    {
        return $this->codExpedicao;
    }

    /**
     * @return mixed
     */
    public function getSequencia()
    {
        return $this->sequencia;
    }

    /**
     * @param mixed $sequencia
     */
    public function setSequencia($sequencia)
    {
        $this->sequencia = $sequencia;
    }

}