<?php

namespace Wms\Domain\Entity;

/**
 * Fabricante
 *
 * @Table(name="FABRICANTE")
 * @Entity(repositoryClass="Wms\Domain\Entity\FabricanteRepository")
 */
class Fabricante
{

    /**
     * @Column(name="COD_FABRICANTE", type="string", nullable=false)
     * @Id
     * @var string
     */
    protected $id;

    /**
     * @Column(name="NOM_FABRICANTE", type="string", length=255, nullable=false)
     * @var string
     */
    protected $nome;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getNome()
    {
        return $this->nome;
    }

    public function setNome($nome)
    {
        $this->nome = $nome;
        return $this;
    }

}