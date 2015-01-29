<?php

namespace Wms\Domain\Entity\Armazenagem\Estrutura;

/**
 * @Table(name="TIPO_EST_ARMAZ")
 * @Entity(repositoryClass="Wms\Domain\Entity\Armazenagem\Estrutura\TipoRepository")
 */
class Tipo
{

    /**
     * @Column(name="COD_TIPO_EST_ARMAZ", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_TIPO_EST_ARMAZ_01", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @Column(name="DSC_TIPO_EST_ARMAZ", type="string", length=255, nullable=true)
     */
    private $descricao;

    public function getId()
    {
        return $this->id;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = mb_strtoupper($descricao, 'UTF-8');
        return $this;
    }

}