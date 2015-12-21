<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="VOLUME_PATRIMONIO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\VolumePatrimonioRepository")
 */
class VolumePatrimonio
{
    /**
     * @Id
     * @Column(name="COD_VOLUME_PATRIMONIO", type="integer", nullable=false)
     */
    protected $id;

    /**
     * @Column(name="DSC_VOLUME_PATRIMONIO", type="string", nullable=true)
     */
    protected $descricao;

    /**
     * @Column(name="IND_OCUPADO", type="string", nullable=true)
     */
    protected $ocupado;


    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setId($id)
    {
        $this->id = '13'.$id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOcupado($ocupado)
    {
        $this->ocupado = $ocupado;
    }

    public function getOcupado()
    {
        return $this->ocupado;
    }

}