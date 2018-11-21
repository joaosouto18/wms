<?php

namespace Wms\Domain\Entity\MapaSeparacao;

/**
 * Rota
 *
 * @Table(name="ROTA")
 * @Entity(repositoryClass="Wms\Domain\Entity\MapaSeparacao\RotaRepository")
 */
class Rota
{

    /**
     * @Column(name="COD_ROTA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ROTA_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="NOME_ROTA", type="string", length=50, nullable=true)
     */
    protected $nomeRota;

    /**
     * @Column(name="COD_ROTA_EXTERNO", type="string", length=50, nullable=true)
     */
    protected $codRotaExterno;

    public function setNomeRota($nomeRota)
    {
        $this->nomeRota = $nomeRota;
    }

    public function getNomeRota()
    {
        return $this->nomeRota;
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
    public function getCodRotaExterno()
    {
        return $this->codRotaExterno;
    }

    /**
     * @param mixed $codRotaExterno
     */
    public function setCodRotaExterno($codRotaExterno)
    {
        $this->codRotaExterno = $codRotaExterno;
    }

}