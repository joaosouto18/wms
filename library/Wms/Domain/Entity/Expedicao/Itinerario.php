<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 * Itinerario
 *
 * @Table(name="ITINERARIO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\ItinerarioRepository")
 */
class Itinerario
{

    /**
     * @Id
     * @Column(name="COD_ITINERARIO", type="string", nullable=false)
     */
    protected $id;

    /**
     * @Column(name="DSC_ITINERARIO", type="string", length=200, nullable=false)
     */
    protected $descricao;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

}