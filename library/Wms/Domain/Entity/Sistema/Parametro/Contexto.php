<?php

namespace Wms\Domain\Entity\Sistema\Parametro;

/**
 * ContextoParametro
 *
 * @Table(name="CONTEXTO_PARAMETRO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Sistema\Parametro\ContextoRepository")
 */
class Contexto
{

    /**
     * @Column(name="COD_CONTEXTO_PARAMETRO", type="smallint", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_CONTEXTO_PARAMETRO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DSC_CONTEXTO_PARAMETRO", type="string", length=60, nullable=true)
     */
    protected $descricao;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Sistema\Parametro", mappedBy="Wms\Domain\Entity\Sistema\Parametro\Contexto")
     */
    protected $parametros;

    public function __construct()
    {
        $this->parametros = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return smallint $id
     */
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

    /**
     *
     * @return object 
     */
    public function getParametros()
    {
        return $this->parametros;
    }

}