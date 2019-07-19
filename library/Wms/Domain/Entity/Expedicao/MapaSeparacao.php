<?php

namespace Wms\Domain\Entity\Expedicao;

use Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="MAPA_SEPARACAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository")
 */
class MapaSeparacao
{
//     * @GeneratedValue(strategy="SEQUENCE")
//     * @SequenceGenerator(sequenceName="SQ_MAPA_SEPARACAO_01", initialValue=1, allocationSize=1)
    /**
     * @Id
     * @Column(name="COD_MAPA_SEPARACAO", type="integer", nullable=false)
     */
    protected $id;

    /**
     * @var Expedicao
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao")
     * @JoinColumn(name="COD_EXPEDICAO", referencedColumnName="COD_EXPEDICAO")
     */
    protected $expedicao;

    /**
     * @Column(name="COD_EXPEDICAO", type="integer", nullable=false)
     */
    protected $codExpedicao;

    /**
     * @Column(name="DTH_CRIACAO", type="datetime", nullable=true)
     */
    protected $dataCriacao;

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
     * @Column(name="DSC_QUEBRA", type="string", nullable=false)
     */
    protected $dscQuebra;

    public function __construct($id)
    {
        $this->id = $id;
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
     * @param mixed $dataCriacao
     */
    public function setDataCriacao($dataCriacao)
    {
        $this->dataCriacao = $dataCriacao;
    }

    /**
     * @return mixed
     */
    public function getDataCriacao()
    {
        return $this->dataCriacao;
    }

    /**
     * @param mixed $dscQuebra
     */
    public function setDscQuebra($dscQuebra)
    {
        $this->dscQuebra = $dscQuebra;
    }

    /**
     * @return mixed
     */
    public function getDscQuebra()
    {
        return $this->dscQuebra;
    }

    /**
     * @param mixed $expedicao
     */
    public function setExpedicao($expedicao)
    {
        $this->expedicao = $expedicao;
    }

    /**
     * @return Expedicao
     */
    public function getExpedicao()
    {
        return $this->expedicao;
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

    /**
     * @return mixed
     */
    public function getCodExpedicao()
    {
        return $this->codExpedicao;
    }

    /**
     * @param mixed $codExpedicao
     */
    public function setCodExpedicao($codExpedicao)
    {
        $this->codExpedicao = $codExpedicao;
    }

}