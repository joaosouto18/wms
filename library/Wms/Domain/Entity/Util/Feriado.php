<?php
namespace Wms\Domain\Entity\Util;


/**
 * Feriado
 *
 * @Table(name="FERIADO")
 * @Entity
 */
class Feriado
{
    /**
     * @var smallint $id
     *
     * @Column(name="COD_FERIADO", type="smallint", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="FERIADO_COD_FERIADO_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string $idTipoFeriado
     *
     * @Column(name="COD_TIPO_FERIADO", type="string", length=1, nullable=true)
     */
    private $idTipoFeriado;

    /**
     * @var string $feriado
     *
     * @Column(name="DSC_FERIADO", type="string", length=60, nullable=true)
     */
    private $feriado;

    /**
     * @var integer $anoFeriado
     *
     * @Column(name="NUM_ANO_FERIADO", type="integer", nullable=true)
     */
    private $anoFeriado;

    /**
     * @var integer $diaFeriado
     *
     * @Column(name="NUM_DIA_FERIADO", type="integer", nullable=true)
     */
    private $diaFeriado;

    /**
     * @var integer $mesFeriado
     *
     * @Column(name="NUM_MES_FERIADO", type="integer", nullable=true)
     */
    private $mesFeriado;
    
    
    public function getId()     
    {
	return $this->id;
    }

    public function getIdTipoFeriado()
    {
	return $this->idTipoFeriado;
    }

    public function setIdTipoFeriado($idTipoFeriado)
    {
	$this->idTipoFeriado = $idTipoFeriado;
        return $this;
    }

    public function getFeriado()
    {
	return $this->feriado;
    }

    public function setFeriado($feriado)
    {
	$this->feriado = $feriado;
        return $this;
    }

    public function getAnoFeriado()
    {
	return $this->anoFeriado;
    }

    public function setAnoFeriado($anoFeriado)
    {
	$this->anoFeriado = $anoFeriado;
        return $this;
    }

    public function getDiaFeriado()
    {
	return $this->diaFeriado;
    }

    public function setDiaFeriado($diaFeriado)
    {
	$this->diaFeriado = $diaFeriado;
        return $this;
    }

    public function getMesFeriado()
    {
	return $this->mesFeriado;
    }

    public function setMesFeriado($mesFeriado)
    {
	$this->mesFeriado = $mesFeriado;
        return $this;
    }
}