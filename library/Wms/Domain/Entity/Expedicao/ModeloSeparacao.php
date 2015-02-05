<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="MODELO_SEPARACAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository")
 */
class ModeloSeparacao
{
    /**
     * @Id
     * @Column(name="COD_MODELO_SEPARACAO", type="integer", nullable=false)
     */
    protected $id;

    /**
     * @Column(name="DSC_MODELO_SEPARACAO", type="string", nullable=true)
     */
    protected $descricao;

    /**
     * @Column(name="TIPO_SEPARACAO_FRACIONADO", type="string", nullable=true)
     */
    protected $tipoSeparacaoFracionado;

    /**
     * @Column(name="TIPO_SEPARACAO_NAO_FRACIONADO", type="string", nullable=true)
     */
    protected $tipoSeparacaoNaoFracionado;

    /**
     * @Column(name="UTILIZA_QUEBRA_COLETOR", type="string", nullable=true)
     */
    protected $utilizaQuebraColetor;

    /**
     * @Column(name="UTILIZA_ETIQUETA_MAE", type="string", nullable=true)
     */
    protected $utilizaEtiquetaMae;

    /**
     * @Column(name="UTILIZA_CAIXA_MASTER", type="string", nullable=true)
     */
    protected $utilizaCaixaMaster;

    /**
     * @Column(name="QUEBRA_PULMA_DOCA", type="string", nullable=true)
     */
    protected $quebraPulmaDoca;
    /**
     * @Column(name="TIPO_QUEBRA_VOLUME", type="string", nullable=true)
     */
    protected $tipoQuebraVolume;

    /**
     * @Column(name="TIPO_DEFAUL_EMBALADO", type="string", nullable=true)
     */
    protected $tipoDefaultEmbalado;

    /**
     * @Column(name="TIPO_CONFERENCIA_EMBALADO", type="string", nullable=true)
     */
    protected $tipoConferenciaEmbalado;

    /**
     * @Column(name="TIPO_CONFERENCIA_NAO_EMBALADO", type="string", nullable=true)
     */
    protected $tipoConferenciaNaoEmbalado;


    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTipoSeparacaoFracionado()
    {
        return $this->tipoSeparacaoFracionado;
    }

    public function setTipoSeparacaoFracionado($tipoSeparacaoFracionado)
    {
        $this->tipoSeparacaoFracionado = $tipoSeparacaoFracionado;
    }

    public function getTipoSeparacaoNaoFracionado()
    {
        return $this->tipoSeparacaoNaoFracionado;
    }

    public function setTipoSeparacaoNaoFracionado($tipoSeparacaoNaoFracionado)
    {
        $this->tipoSeparacaoNaoFracionado = $tipoSeparacaoNaoFracionado;
    }

    public function getUtilizaQuebraColetor()
    {
        return $this->utilizaQuebraColetor;
    }

    public function setUtilizaQuebraColetor($utilizaQuebraColetor)
    {
        $this->utilizaQuebraColetor = $utilizaQuebraColetor;
    }

    public function getUtilizaEtiquetaMae()
    {
        return $this->utilizaEtiquetaMae;
    }

    public function setUtilizaEtiquetaMae($utilizaEtiquetaMae)
    {
        $this->utilizaEtiquetaMae = $utilizaEtiquetaMae;
    }

    public function getUtilizaCaixaMaster()
    {
        return $this->utilizaCaixaMaster;
    }

    public function setUtilizaCaixaMaster($utilizaCaixaMaster)
    {
        $this->utilizaCaixaMaster = $utilizaCaixaMaster;
    }

    public function getQuebraPulmaDoca()
    {
        return $this->quebraPulmaDoca;
    }

    public function setQuebraPulmaDoca($quebraPulmaDoca)
    {
        $this->quebraPulmaDoca = $quebraPulmaDoca;
    }

    public function getTipoQuebraVolume()
    {
        return $this->tipoQuebraVolume;
    }

    public function setTipoQuebraVolume($tipoQuebraVolume)
    {
        $this->tipoQuebraVolume = $tipoQuebraVolume;
    }

    public function getTipoDefaultEmbalado()
    {
        return $this->tipoDefaultEmbalado;
    }

    public function setTipoDefaultEmbalado($tipoDefaultEmbalado)
    {
        $this->tipoDefaultEmbalado = $tipoDefaultEmbalado;
    }

    public function getTipoConferenciaEmbalado()
    {
        return $this->tipoConferenciaEmbalado;
    }

    public function setTipoConferenciaEmbalado($tipoConferenciaEmbalado)
    {
        $this->tipoConferenciaEmbalado = $tipoConferenciaEmbalado;
    }

    public function getTipoConferenciaNaoEmbalado()
    {
        return $this->tipoConferenciaNaoEmbalado;
    }

    public function setTipoConferenciaNaoEmbalado($tipoConferenciaNaoEmbalado)
    {
        $this->tipoConferenciaNaoEmbalado = $tipoConferenciaNaoEmbalado;
    }



}