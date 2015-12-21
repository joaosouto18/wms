<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="ETIQUETA_MAE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\EtiquetaMaeRepository")
 */
class EtiquetaMae
{
    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_ETIQUETA_MAE", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_ETIQUETA_MAE_01", initialValue=1, allocationSize=1)
     */
    protected $id;


    /**
     * @Column(name="DSC_QUEBRA", type="string", nullable=true)
     */
    protected $dscQuebra;

    /**
     * @Column(name="COD_EXPEDICAO", type="integer", nullable=true)
     */
    protected $codExpedicao;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao")
     * @JoinColumn(name="COD_EXPEDICAO", referencedColumnName="COD_EXPEDICAO")
     */
    protected $expedicao;

    public function setDscQuebra($dscQuebra)
    {
        $this->dscQuebra = $dscQuebra;
    }

    public function getDscQuebra()
    {
        return $this->dscQuebra;
    }

    public function setExpedicao($expedicao)
    {
        $this->expedicao = $expedicao;
    }

    public function getExpedicao()
    {
        return $this->expedicao;
    }

    public function setCodExpedicao($codExpedicao)
    {
        $this->codExpedicao = $codExpedicao;
    }

    public function getCodExpedicao()
    {
        return $this->codExpedicao;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}