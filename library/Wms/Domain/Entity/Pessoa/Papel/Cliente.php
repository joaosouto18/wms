<?php

namespace Wms\Domain\Entity\Pessoa\Papel;

use Wms\Domain\Entity\Pessoa,
    Wms\Domain\Entity\Ator;

/**
 * Cliente
 *
 * @Table(name="CLIENTE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Pessoa\Papel\ClienteRepository")
 */
class Cliente extends Emissor implements Ator {

    /**
     * @var string
     * @Column(name="COD_EXTERNO", type="string", nullable=false)
     */
    protected $codExterno;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\MapaSeparacao\Praca")
     * @JoinColumn(name="COD_PRACA", referencedColumnName="COD_PRACA")
     */
    protected $praca;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\MapaSeparacao\Rota")
     * @JoinColumn(name="COD_ROTA", referencedColumnName="COD_ROTA")
     */
    protected $rota;

    public function setCodExterno($codExterno)
    {
        $this->codExterno = $codExterno;
    }

    public function getCodExterno()
    {
        return $this->codExterno;
    }

    public function getPraca()
    {
        return $this->praca;
    }

    public function setPraca($praca)
    {
        $this->praca = $praca;
    }

    /**
     * @return mixed
     */
    public function getRota()
    {
        return $this->rota;
    }

    /**
     * @param mixed $rota
     */
    public function setRota($rota)
    {
        $this->rota = $rota;
    }

}