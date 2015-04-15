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
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_MODELO_SEPARACAO_01", allocationSize=1, initialValue=1)
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

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Expedicao\ModeloSeparacaoTipoQuebraFracionado", mappedBy="modeloSeparacao", cascade={"persist", "remove"})
     * @var ArrayCollection tipos de quebra para fracionados
     */
    protected $tiposQuebraFracionado;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Expedicao\ModeloSeparacaoTipoQuebraNaoFracionado", mappedBy="modeloSeparacao", cascade={"persist", "remove"})
     * @var ArrayCollection tipos de quebra para nao fracionados
     */
    protected $tiposQuebraNaoFracionado;

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
    public function getDescricao()
    {
        return $this->descricao;
    }

    /**
     * @param mixed $descricao
     */
    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
    }

    /**
     * @return mixed
     */
    public function getTipoSeparacaoFracionado()
    {
        return $this->tipoSeparacaoFracionado;
    }

    /**
     * @param mixed $tipoSeparacaoFracionado
     */
    public function setTipoSeparacaoFracionado($tipoSeparacaoFracionado)
    {
        $this->tipoSeparacaoFracionado = $tipoSeparacaoFracionado;
    }

    /**
     * @return mixed
     */
    public function getTipoSeparacaoNaoFracionado()
    {
        return $this->tipoSeparacaoNaoFracionado;
    }

    /**
     * @param mixed $tipoSeparacaoNaoFracionado
     */
    public function setTipoSeparacaoNaoFracionado($tipoSeparacaoNaoFracionado)
    {
        $this->tipoSeparacaoNaoFracionado = $tipoSeparacaoNaoFracionado;
    }

    /**
     * @return mixed
     */
    public function getUtilizaQuebraColetor()
    {
        return $this->utilizaQuebraColetor;
    }

    /**
     * @param mixed $utilizaQuebraColetor
     */
    public function setUtilizaQuebraColetor($utilizaQuebraColetor)
    {
        $this->utilizaQuebraColetor = $utilizaQuebraColetor;
    }

    /**
     * @return mixed
     */
    public function getUtilizaEtiquetaMae()
    {
        return $this->utilizaEtiquetaMae;
    }

    /**
     * @param mixed $utilizaEtiquetaMae
     */
    public function setUtilizaEtiquetaMae($utilizaEtiquetaMae)
    {
        $this->utilizaEtiquetaMae = $utilizaEtiquetaMae;
    }

    /**
     * @return mixed
     */
    public function getUtilizaCaixaMaster()
    {
        return $this->utilizaCaixaMaster;
    }

    /**
     * @param mixed $utilizaCaixaMaster
     */
    public function setUtilizaCaixaMaster($utilizaCaixaMaster)
    {
        $this->utilizaCaixaMaster = $utilizaCaixaMaster;
    }

    /**
     * @return mixed
     */
    public function getQuebraPulmaDoca()
    {
        return $this->quebraPulmaDoca;
    }

    /**
     * @param mixed $quebraPulmaDoca
     */
    public function setQuebraPulmaDoca($quebraPulmaDoca)
    {
        $this->quebraPulmaDoca = $quebraPulmaDoca;
    }

    /**
     * @return mixed
     */
    public function getTipoQuebraVolume()
    {
        return $this->tipoQuebraVolume;
    }

    /**
     * @param mixed $tipoQuebraVolume
     */
    public function setTipoQuebraVolume($tipoQuebraVolume)
    {
        $this->tipoQuebraVolume = $tipoQuebraVolume;
    }

    /**
     * @return mixed
     */
    public function getTipoDefaultEmbalado()
    {
        return $this->tipoDefaultEmbalado;
    }

    /**
     * @param mixed $tipoDefaultEmbalado
     */
    public function setTipoDefaultEmbalado($tipoDefaultEmbalado)
    {
        $this->tipoDefaultEmbalado = $tipoDefaultEmbalado;
    }

    /**
     * @return mixed
     */
    public function getTipoConferenciaEmbalado()
    {
        return $this->tipoConferenciaEmbalado;
    }

    /**
     * @param mixed $tipoConferenciaEmbalado
     */
    public function setTipoConferenciaEmbalado($tipoConferenciaEmbalado)
    {
        $this->tipoConferenciaEmbalado = $tipoConferenciaEmbalado;
    }

    /**
     * @return mixed
     */
    public function getTipoConferenciaNaoEmbalado()
    {
        return $this->tipoConferenciaNaoEmbalado;
    }

    /**
     * @param mixed $tipoConferenciaNaoEmbalado
     */
    public function setTipoConferenciaNaoEmbalado($tipoConferenciaNaoEmbalado)
    {
        $this->tipoConferenciaNaoEmbalado = $tipoConferenciaNaoEmbalado;
    }

    /**
     * @return ArrayCollection
     */
    public function getTiposQuebraFracionado()
    {
        return $this->tiposQuebraFracionado;
    }

    /**
     * @param ArrayCollection $tiposQuebraFracionado
     */
    public function setTiposQuebraFracionado($tiposQuebraFracionado)
    {
        $this->tiposQuebraFracionado = $tiposQuebraFracionado;
    }

    /**
     * @return ArrayCollection
     */
    public function getTiposQuebraNaoFracionado()
    {
        return $this->tiposQuebraNaoFracionado;
    }

    /**
     * @param ArrayCollection $tiposQuebraNaoFracionado
     */
    public function setTiposQuebraNaoFracionado($tiposQuebraNaoFracionado)
    {
        $this->tiposQuebraNaoFracionado = $tiposQuebraNaoFracionado;
    }






}