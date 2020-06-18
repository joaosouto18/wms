<?php

namespace Wms\Domain\Entity\Expedicao;

use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\OrdemServico;
use Wms\Domain\Entity\Pessoa\Papel\Cliente;

/**
 * @Table(name="CONF_CARREG_VOLUME")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\ConfCarregVolumeRepository")
 */
class ConfCarregVolume
{

    const VOL_TIPO_EMBALADO = 'VE';
    const VOL_TIPO_ETIQ_SEP = 'ES';
    const VOL_TIPO_PATRIMONIO = 'VP';

    /**
     * @var int
     * @Column(name="COD_CONF_CARREG_VOL", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_CONF_CARREG_VOL_01", allocationSize=1, initialValue=1)
     * @GeneratedValue(strategy="SEQUENCE")
     * @Id
     */
    protected $id;

    /**
     * @var ConfCarregOs
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\ConfCarregOs")
     * @JoinColumn(name="COD_CONF_CARREG_OS", referencedColumnName="COD_CONF_CARREG_OS")
     */
    protected $confCarregOs;

    /**
     * @var int
     * @Column(name="COD_VOLUME", type="integer", nullable=false)
     */
    protected $codVolume;

    /**
     * @var string
     * @Column(name="IND_TIPO_VOLUME", type="string", nullable=false)
     */
    protected $tipoVolume;

    /**
     * @var \DateTime
     * @Column(name="DTH_CONFERENCIA", type="datetime", nullable=true)
     */
    protected $dthConferencia;

    public function __construct()
    {
        self::setDthConferencia(new \DateTime());
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
     * @return int
     */
    public function getCodVolume()
    {
        return $this->codVolume;
    }

    /**
     * @param int $codVolume
     */
    public function setCodVolume($codVolume)
    {
        $this->codVolume = $codVolume;
    }

    /**
     * @return string
     */
    public function getTipoVolume()
    {
        return $this->tipoVolume;
    }

    /**
     * @param string $tipoVolume
     */
    public function setTipoVolume($tipoVolume)
    {
        $this->tipoVolume = $tipoVolume;
    }

    /**
     * @return \DateTime
     */
    public function getDthConferencia()
    {
        return $this->dthConferencia;
    }

    /**
     * @param \DateTime $dthConferencia
     */
    public function setDthConferencia($dthConferencia)
    {
        $this->dthConferencia = $dthConferencia;
    }

    /**
     * @return ConfCarregOs
     */
    public function getConfCarregOs()
    {
        return $this->confCarregOs;
    }

    /**
     * @param ConfCarregOs $confCarregOs
     */
    public function setConfCarregOs($confCarregOs)
    {
        $this->confCarregOs = $confCarregOs;
    }

}