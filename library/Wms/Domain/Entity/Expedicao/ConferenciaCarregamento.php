<?php

namespace Wms\Domain\Entity\Expedicao;

use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\OrdemServico;
use Wms\Domain\Entity\Pessoa\Papel\Cliente;

/**
 * @Table(name="CONFERENCIA_CARREGAMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\ConferenciaCarregamentoRepository")
 */
class ConferenciaCarregamento
{

    const VOL_TIPO_EMBALADO = 'VE';
    const VOL_TIPO_ETIQ_SEP = 'ES';

    /**
     * @var int
     * @Column(name="COD_CONF_CARREG", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_CONF_CARREG_01", allocationSize=1, initialValue=1)
     * @GeneratedValue(strategy="SEQUENCE")
     * @Id
     */
    protected $id;

    /**
     * @var Cliente
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa\Papel\Cliente")
     * @JoinColumn(name="COD_CLIENTE", referencedColumnName="COD_PESSOA")
     */
    protected $cliente;

    /**
     * @var int
     * @Column(name="COD_CLIENTE", type="integer")
     */
    protected $codCliente;

    /**
     * @var Expedicao
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao")
     * @JoinColumn(name="COD_EXPEDICAO", referencedColumnName="COD_EXPEDICAO")
     */
    protected $expedicao;

    /**
     * @var int
     * @Column(name="COD_EXPEDICAO", type="integer")
     */
    protected $codExpedicao;

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
     * @var OrdemServico
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS")
     */
    protected $ordemServico;

    /**
     * @var int
     * @Column(name="COD_OS", type="integer")
     */
    protected $codOS;

    /**
     * @var \DateTime
     * @Column(name="DTH_CONFERENCIA", type="datetime", nullable=true)
     */
    protected $dthConferencia;

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
     * @return Cliente
     */
    public function getCliente()
    {
        return $this->cliente;
    }

    /**
     * @param Cliente $cliente
     */
    public function setCliente($cliente)
    {
        $this->cliente = $cliente;
    }

    /**
     * @return int
     */
    public function getCodCliente()
    {
        return $this->codCliente;
    }

    /**
     * @param int $codCliente
     */
    public function setCodCliente($codCliente)
    {
        $this->codCliente = $codCliente;
    }

    /**
     * @return Expedicao
     */
    public function getExpedicao()
    {
        return $this->expedicao;
    }

    /**
     * @param Expedicao $expedicao
     */
    public function setExpedicao($expedicao)
    {
        $this->expedicao = $expedicao;
    }

    /**
     * @return int
     */
    public function getCodExpedicao()
    {
        return $this->codExpedicao;
    }

    /**
     * @param int $codExpedicao
     */
    public function setCodExpedicao($codExpedicao)
    {
        $this->codExpedicao = $codExpedicao;
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
     * @return OrdemServico
     */
    public function getOrdemServico()
    {
        return $this->ordemServico;
    }

    /**
     * @param OrdemServico $ordemServico
     */
    public function setOrdemServico($ordemServico)
    {
        $this->ordemServico = $ordemServico;
    }

    /**
     * @return int
     */
    public function getCodOS()
    {
        return $this->codOS;
    }

    /**
     * @param int $codOS
     */
    public function setCodOS($codOS)
    {
        $this->codOS = $codOS;
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

}