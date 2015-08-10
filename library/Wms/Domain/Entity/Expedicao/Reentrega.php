<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 * Carga
 *
 * @Table(name="REENTREGA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\ReentregaRepository")
 */
class Reentrega
{
    /**
     * @Column(name="COD_REENTREGA", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_REENTREGA_01", allocationSize=1, initialValue=1)
     * @GeneratedValue(strategy="SEQUENCE")
     * @Id
     */
    protected $id;

    /**
     * @Column(name="COD_CARGA", type="integer", nullable=false)
     */
    protected $codCarga;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\Carga")
     * @JoinColumn(name="COD_CARGA", referencedColumnName="COD_CARGA")
     */
    protected $carga;

    /**
     * @Column(name="COD_NOTA_FISCAL_SAIDA", type="integer", nullable=false)
     */
    protected $codNotaFiscalSaida;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\NotaFiscalSaida")
     * @JoinColumn(name="COD_NOTA_FISCAL_SAIDA", referencedColumnName="COD_NOTA_FISCAL_SAIDA")
     */
    protected $notaFiscalSaida;

    /**
     * @Column(name="DTH_REENTREGA", type="datetime", nullable=false)
     */
    protected $dataReentrega;

    /**
     * @Column(name="IND_ETIQUETA_MAPA_GERADO", type="string", nullable=false)
     */
    protected $indEtiquetaMapaGerado;

    /**
     * @param mixed $carga
     */
    public function setCarga($carga)
    {
        $this->carga = $carga;
    }

    /**
     * @return mixed
     */
    public function getCarga()
    {
        return $this->carga;
    }

    /**
     * @param mixed $codCarga
     */
    public function setCodCarga($codCarga)
    {
        $this->codCarga = $codCarga;
    }

    /**
     * @return mixed
     */
    public function getCodCarga()
    {
        return $this->codCarga;
    }

    /**
     * @param mixed $codNotaFiscalSaida
     */
    public function setCodNotaFiscalSaida($codNotaFiscalSaida)
    {
        $this->codNotaFiscalSaida = $codNotaFiscalSaida;
    }

    /**
     * @return mixed
     */
    public function getCodNotaFiscalSaida()
    {
        return $this->codNotaFiscalSaida;
    }

    /**
     * @param mixed $dataReentrega
     */
    public function setDataReentrega($dataReentrega)
    {
        $this->dataReentrega = $dataReentrega;
    }

    /**
     * @return mixed
     */
    public function getDataReentrega()
    {
        return $this->dataReentrega;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $indEtiquetaMapaGerado
     */
    public function setIndEtiquetaMapaGerado($indEtiquetaMapaGerado)
    {
        $this->indEtiquetaMapaGerado = $indEtiquetaMapaGerado;
    }

    /**
     * @return mixed
     */
    public function getIndEtiquetaMapaGerado()
    {
        return $this->indEtiquetaMapaGerado;
    }

    /**
     * @param mixed $notaFiscalSaida
     */
    public function setNotaFiscalSaida($notaFiscalSaida)
    {
        $this->notaFiscalSaida = $notaFiscalSaida;
    }

    /**
     * @return mixed
     */
    public function getNotaFiscalSaida()
    {
        return $this->notaFiscalSaida;
    }

}