<?php
namespace Wms\Domain\Entity\Sistema\Recurso;

/**
 * Mascara
 *
 * @Table(name="MASCARA_RECURSO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Sistema\Recurso\MascaraRepository")
 */
class Mascara
{

    /**
     * @var smallint $id
     * @Column(name="COD_MASCARA_RECURSO", type="smallint", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_MASCARA_RECURSO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var Wms\Domain\Entity\Sistema\Recurso $recurso
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Sistema\Recurso", inversedBy="mascaras")
     * @JoinColumn(name="COD_RECURSO", referencedColumnName="COD_RECURSO") 
     */
    protected $recurso;

    /**
     * @var date $datInicioVigencia
     * @Column(name="DAT_INICIO_VIGENCIA", type="date", nullable=false)
     */
    protected $datInicioVigencia;

    /**
     * @var date $datFinalVigencia
     * @Column(name="DAT_FINAL_VIGENCIA", type="date", nullable=false)
     */
    protected $datFinalVigencia;

    /**
     * @var string $dscMascaraAuditoria
     * @Column(name="DSC_MASCARA_AUDITORIA", type="string", length=255, nullable=true)
     */
    protected $dscMascaraAuditoria;

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

    public function getDatInicioVigencia()
    {
	return ($this->datInicioVigencia == null) ? null : $this->datInicioVigencia->format('d/m/Y');
    }

    /**
     * Atribui a data inicial de vigencia
     * @param \DateTime $datInicioVigencia 
     */
    public function setDatInicioVigencia(\DateTime $datInicioVigencia)
    {
	$this->datInicioVigencia = $datInicioVigencia;
        return $this;
    }

    public function getDatFinalVigencia()
    {
	return ($this->datFinalVigencia == null) ? null : $this->datFinalVigencia->format('d/m/Y');
    }

    /**
     * Atribui a data final de vigencia
     * @param \DateTime $datFinalVigencia 
     */
    public function setDatFinalVigencia(\DateTime $datFinalVigencia)
    {
	$this->datFinalVigencia = $datFinalVigencia;
        return $this;
    }

    public function getDscMascaraAuditoria()
    {
	return $this->dscMascaraAuditoria;
    }

    public function setDscMascaraAuditoria($dscMascaraAuditoria)
    {
	$this->dscMascaraAuditoria = $dscMascaraAuditoria;
        return $this;
    }

}