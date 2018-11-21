<?php
namespace Wms\Domain\Entity\Sistema\Recurso;

/**
 * Auditoria
 *
 * @Table(name="AUDITORIA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Sistema\Recurso\AuditoriaRepository")
 */
class Auditoria
{

    /**
     * @var smallint $id
     * @Column(name="COD_AUDITORIA", type="smallint", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_AUDITORIA_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var Wms\Domain\Entity\Sistema\Recurso $recurso
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Sistema\Recurso")
     * @JoinColumn(name="COD_RECURSO", referencedColumnName="COD_RECURSO") 
     */
    protected $recurso;

    /**
     * @var Wms\Domain\Entity\Usuario $usuario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario", cascade={"persist"})
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO") 
     */
    protected $usuario;

    /**
     * @var Wms\Domain\Entity\Filial $filial
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Filial")
     * @JoinColumn(name="COD_FILIAL", referencedColumnName="COD_FILIAL") 
     */
    protected $filial;

    /**
     * @var date $datOperacao
     * @Column(name="DTH_OPERACAO", type="datetime", nullable=false)
     */
    protected $datOperacao;

    /**
     * @var string $dscOperacao
     * @Column(name="DSC_OPERACAO", type="string", length=255, nullable=true)
     */
    protected $dscOperacao;

    public function getId()
    {
	return $this->id;
    }

    public function getRecurso()
    {
	return $this->recurso;
    }

    public function setRecurso($recurso)
    {
	$this->recurso = $recurso;
        return $this;
    }

    public function getUsuario()
    {
	return $this->usuario;
    }

    public function setUsuario($usuario)
    {
	$this->usuario = $usuario;
        return $this;
    }

    public function getFilial()
    {
	return $this->filial;
    }

    public function setFilial($filial)
    {
	$this->filial = $filial;
        return $this;
    }

    public function getDatOperacao()
    {
	return $this->datOperacao->format('d/m/Y à\s H:i:s');
    }

    public function setDatOperacao(\DateTime $datOperacao)
    {
	$this->datOperacao = $datOperacao;
        return $this;
    }

    public function getDscOperacao()
    {
	return $this->dscOperacao;
    }

    public function setDscOperacao($dscOperacao)
    {
	$this->dscOperacao = mb_strtoupper($dscOperacao, 'UTF-8');
        return $this;
    }

}