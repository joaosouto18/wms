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

    /**
     * @Column(name="COD_PRACA_EXTERNO", type="string", length=50, nullable=false)
     */
    protected $codPracaExterno;


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
    public function getCodPracaExterno()
    {
        return $this->codPracaExterno;
    }

    /**
     * @param mixed $codPracaExterno
     */
    public function setCodPracaExterno($codPracaExterno)
    {
        $this->codPracaExterno = $codPracaExterno;
    }

}