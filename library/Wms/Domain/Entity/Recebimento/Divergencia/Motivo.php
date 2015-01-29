<?php

namespace Wms\Domain\Entity\Recebimento\Divergencia;

/**
 * Atividade
 *
 * @Table(name="MOTIVO_DIVER_RECEB")
 * @Entity(repositoryClass="Wms\Domain\Entity\Recebimento\Divergencia\MotivoRepository")
 */
class Motivo
{

    /**
     * @Id
     * @var smallint $id
     * @Column(name="COD_MOTIVO_DIVER_RECEB", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_MOTIVO_DIVER_RECEB_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var string $descricao
     * @Column(name="DSC_MOTIVO_DIVER_RECEB", type="string", length=255, nullable=false)
     */
    protected $descricao;
    
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