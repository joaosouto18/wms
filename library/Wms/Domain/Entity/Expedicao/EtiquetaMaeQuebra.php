<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="ETIQUETA_MAE_QUEBRA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\EtiquetaMaeQuebraRepository")
 */
class EtiquetaMaeQuebra
{
    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_ETIQUETA_MAE_QUEBRA", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_ETIQUETA_MAE_QUEBRA_01", initialValue=1, allocationSize=1)
     */
    protected $id;


    /**
     * @Column(name="IND_TIPO_QUEBRA", type="string", nullable=true)
     */
    protected $indTipoQuebra;

    /**
     * @Column(name="COD_QUEBRA", type="integer", nullable=true)
     */
    protected $codQuebra;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\EtiquetaMae")
     * @JoinColumn(name="COD_ETIQUETA_MAE", referencedColumnName="COD_ETIQUETA_MAE")
     */
    protected $etiquetaMae;

    /**
     * @Column(name="COD_ETIQUETA_MAE", type="integer", nullable=true)
     */
    protected $codEtiquetaMae;


    /**
     * @Column(name="TIPO_FRACAO", type="string", nullable=true)
     */
    protected $tipoFracao;

    public function setIndTipoQuebra($indTipoQuebra)
    {
        $this->indTipoQuebra = $indTipoQuebra;
    }

    public function getIndTipoQuebra()
    {
        return $this->indTipoQuebra;
    }

    public function setCodQuebra($codQuebra)
    {
        $this->codQuebra = $codQuebra;
    }

    public function getCodQuebra()
    {
        return $this->codQuebra;
    }

    public function setCodEtiquetaMae($codEtiquetaMae)
    {
        $this->codEtiquetaMae = $codEtiquetaMae;
    }

    public function getCodEtiquetaMae()
    {
        return $this->codEtiquetaMae;
    }

    public function setEtiquetaMae($etiquetaMae)
    {
        $this->etiquetaMae = $etiquetaMae;
    }

    public function getEtiquetaMae()
    {
        return $this->etiquetaMae;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }


    public function setTipoFracao($tipoFracao)
    {
        $this->tipoFracao = $tipoFracao;
    }

    public function getTipoFracao()
    {
        return $this->tipoFracao;
    }

}