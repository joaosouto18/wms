<?php

namespace Wms\Domain\Entity\Util;

/**
 * @Table(name="SIGLA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Util\SiglaRepository")
 */
class Sigla
{

    /**
     * @var integer $id
     *
     * @Column(name="COD_SIGLA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_SIGLA_01", allocationSize=1, initialValue=1)
     */
    protected $id;
    /**
     * @var string $idReferenciaSigla
     *
     * @Column(name="COD_REFERENCIA_SIGLA", type="string", length=20, nullable=true)
     */
    protected $referencia;
    
    /**
     * @var string $sigla
     *
     * @Column(name="DSC_SIGLA", type="string", length=60, nullable=true)
     */
    protected $sigla;
    
    /**
     * @var Wms\Domain\Entity\Util\Sigla\Tipo
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla\Tipo")
     * @JoinColumn(name="COD_TIPO_SIGLA", referencedColumnName="COD_TIPO_SIGLA") 
     */
    protected $tipo;

    public function getId()
    {
	return $this->id;
    }

    public function getReferencia()
    {
	return $this->referencia;
    }

    public function setReferencia($referencia)
    {
	$this->referencia = $referencia;
        return $this;
    }

    public function getTipo()
    {
	return $this->tipo;
    }

    public function setTipo($tipo)
    {
	$this->tipo = $tipo;
        return $this;
    }

    public function getSigla()
    {
	return $this->sigla;
    }

    public function setSigla($sigla)
    {
	$this->sigla = mb_strtoupper($sigla, 'UTF-8');
        return $this;
    }

}