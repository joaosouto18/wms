<?php

namespace Wms\Domain\Entity\MapaSeparacao;

/**
 * Praça
 *
 * @Table(name="PRACA")
 * @Entity(repositoryClass="Wms\Domain\Entity\MapaSeparacao\PracaRepository")
 */
class Praca
{

    /**
     * @Column(name="COD_PRACA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PRACA_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="NOME_PRACA", type="string", length=50, nullable=false)
     */
    protected $nomePraca;

    public function setNomePraca($nomePraca)
    {
        $this->nomePraca = $nomePraca;
    }

    public function getNomePraca()
    {
        return $this->nomePraca;
    }


    public function getId()
    {
        return $this->id;
    }

}