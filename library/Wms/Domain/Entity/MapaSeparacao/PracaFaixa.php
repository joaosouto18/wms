<?php

namespace Wms\Domain\Entity\MapaSeparacao;

/**
 * Faixas de Praças por CEP
 *
 * @Table(name="PRACA_FAIXA")
 * @Entity(repositoryClass="Wms\Domain\Entity\MapaSeparacao\PracaFaixaRepository")
 */
class PracaFaixa
{

    /**
     * @Column(name="COD_PRACA_FAIXA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PRACA_FAIXA_01", allocationSize=1, initialValue=1)
     */
    protected $id;


    /**
     * @Column(name="COD_PRACA", type="integer", length=8, nullable=false)
     */
    protected $codPraca;

    /**
     * @Column(name="FAIXA_CEP1", type="string", length=50, nullable=false)
     */
    protected $faixaCep1;

    /**
     * @Column(name="FAIXA_CEP2", type="string", length=50, nullable=false)
     */
    protected $faixaCep2;

    public function setCodPraca($codPraca)
    {
        $this->codPraca = $codPraca;
    }

    public function getCodPraca()
    {
        return $this->codPraca;
    }

    public function setFaixaCep1($faixaCep1)
    {
        $this->faixaCep1 = $faixaCep1;
    }

    public function getFaixaCep1()
    {
        return $this->faixaCep1;
    }

    public function setFaixaCep2($faixaCep2)
    {
        $this->faixaCep2 = $faixaCep2;
    }

    public function getFaixaCep2()
    {
        return $this->faixaCep2;
    }

}