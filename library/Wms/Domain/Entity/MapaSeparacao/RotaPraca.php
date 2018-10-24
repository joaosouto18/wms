<?php

namespace Wms\Domain\Entity\MapaSeparacao;

/**
 * Praças que pertecem a Rota
 *
 * @Table(name="ROTA_PRACA")
 * @Entity(repositoryClass="Wms\Domain\Entity\MapaSeparacao\RotaPracaRepository")
 */
class RotaPraca
{

    /**
     * @Column(name="COD_ROTA_PRACA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ROTA_PRACA_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="COD_ROTA", type="integer", length=8, nullable=false)
     */
    protected $codRota;

    /**
     * @Column(name="COD_PRACA", type="integer", length=8, nullable=false)
     */
    protected $codPraca;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\MapaSeparacao\Rota")
     * @JoinColumn(name="COD_ROTA", referencedColumnName="COD_ROTA")
     */
    protected $rota;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\MapaSeparacao\Praca")
     * @JoinColumn(name="COD_PRACA", referencedColumnName="COD_PRACA")
     */
    protected $praca;

    public function setCodRota($codRota)
    {
        $this->codRota = $codRota;
    }

    public function getCodRota()
    {
        return $this->codRota;
    }

    public function setCodPraca($codPraca)
    {
        $this->codPraca = $codPraca;
    }

    public function getCodPraca()
    {
        return $this->codPraca;
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

    /**
     * @return mixed
     */
    public function getPraca()
    {
        return $this->praca;
    }

    /**
     * @param mixed $praca
     */
    public function setPraca($praca)
    {
        $this->praca = $praca;
    }



}