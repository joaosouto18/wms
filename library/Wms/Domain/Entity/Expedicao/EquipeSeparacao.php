<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="EQUIPE_SEPARACAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\EquipeSeparacaoRepository")
 */
class EquipeSeparacao
{

    /**
     * @Id
     * @Column(name="COD_EQUIPE_SEPARACAO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_EQUIPE_SEPARACA_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DTH_VINCULO", type="datetime", nullable=false)
     */
    protected $dataVinculo;

    /**
     * @Column(name="COD_USUARIO", type="integer", nullable=false)
     */
    protected $codUsuario;

    /**
     * @Column(name="ETIQUETA_INICIAL", type="integer", nullable=false)
     */
    protected $etiquetaInicial;

    /**
     * @Column(name="ETIQUETA_FINAL", type="integer", nullable=false)
     */
    protected $etiquetaFinal;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
    public function getDataVinculo()
    {
        return $this->dataVinculo;
    }

    /**
     * @param mixed $dataVinculo
     */
    public function setDataVinculo($dataVinculo)
    {
        $this->dataVinculo = $dataVinculo;
    }

    /**
     * @return mixed
     */
    public function getCodUsuario()
    {
        return $this->codUsuario;
    }

    /**
     * @param mixed $codUsuario
     */
    public function setCodUsuario($codUsuario)
    {
        $this->codUsuario = $codUsuario;
    }

    /**
     * @return mixed
     */
    public function getEtiquetaInicial()
    {
        return $this->etiquetaInicial;
    }

    /**
     * @param mixed $etiquetaInicial
     */
    public function setEtiquetaInicial($etiquetaInicial)
    {
        $this->etiquetaInicial = $etiquetaInicial;
    }

    /**
     * @return mixed
     */
    public function getEtiquetaFinal()
    {
        return $this->etiquetaFinal;
    }

    /**
     * @param mixed $etiquetaFinal
     */
    public function setEtiquetaFinal($etiquetaFinal)
    {
        $this->etiquetaFinal = $etiquetaFinal;
    }
    
}