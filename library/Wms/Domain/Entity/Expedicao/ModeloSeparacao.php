<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="MODELO_SEPARACAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository")
 */
class ModeloSeparacao
{

    const DEFAULT_EMBALADO_PRODUTO = "P";
    const DEFAULT_EMBALADO_FRACIONADOS = "F";
    const DEFAULT_EMBALADO_TODAS_EMBALAGENS = "T";

    const QUEBRA_VOLUME_CARGA = "A";
    const QUEBRA_VOLUME_CLIENTE = "C";

    const TIPO_AGROUP_VOLS_CLIENTE = "C";
    const TIPO_AGROUP_VOLS_EXPEDICAO = "E";

    const QUEBRA_PULMAO_DOCA_CLIENTE = "C";
    const QUEBRA_PULMAO_DOCA_PRACA = "P";
    const QUEBRA_PULMAO_DOCA_ROTA = 'RT';
    const QUEBRA_PULMAO_DOCA_EXPEDICAO = "E";
    const QUEBRA_PULMAO_DOCA_CARGA = "G";
    const QUEBRA_PULMAO_DOCA_NAO_USA = "N";

    const CONFERENCIA_ITEM_A_ITEM = "I";
    const CONFERENCIA_QUANTIDADE = "Q";

    const TIPO_SEPARACAO_ETIQUETA = "E";
    const TIPO_SEPARACAO_MAPA = "M";

    const TIPO_CONF_CARREG_EXP = 'E';
    const TIPO_CONF_CARREG_DANFE = 'D';

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
     * @Column(name="TIPO_SEPARACAO_FRAC_EMB", type="string", nullable=true)
     */
    protected $tipoSeparacaoFracionadoEmbalado;

    /**
     * @Column(name="TIPO_SEPARACAO_NAO_FRAC_EMB", type="string", nullable=true)
     */
    protected $tipoSeparacaoNaoFracionadoEmbalado;

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
     * @Column(name="IND_SEPARACAO_PC", type="string", nullable=false)
     */
    protected $separacaoPC;

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
     * @var ModeloSeparacaoTipoQuebraFracionado[] tipos de quebra para fracionados
     */
    protected $tiposQuebraFracionado;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Expedicao\ModeloSeparacaoTipoQuebraNaoFracionado", mappedBy="modeloSeparacao", cascade={"persist", "remove"})
     * @var ModeloSeparacaoTipoQuebraNaoFracionado[] tipos de quebra para nao fracionados
     */
    protected $tiposQuebraNaoFracionado;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Expedicao\ModeloSeparacaoTipoQuebraEmbalado", mappedBy="modeloSeparacao", cascade={"persist", "remove"})
     * @var ModeloSeparacaoTipoQuebraEmbalado[] tipos de quebra para nao fracionados
     */
    protected $tiposQuebraEmbalado;

    /**
     * @column(name="IND_IMPRIME_ETQ_VOLUME", type="string", nullable=true)
     */
    protected $imprimeEtiquetaVolume;

    /**
     * @Column(name="UTILIZA_VOLUME_PATRIMONIO", type="string", nullable=true)
     */
    protected $utilizaVolumePatrimonio;

    /**
     * @Column(name="AGRUP_CONT_ETIQUETAS", type="string", nullable=true)
     */
    protected $agrupContEtiquetas;

    /**
     * @Column(name="TIPO_SEQ_VOLS", type="string", nullable=true)
     */
    protected $tipoAgroupSeqEtiquetas;

    /**
     * @Column(name="USA_CAIXA_PADRAO", type="string", nullable=true)
     */
    protected $usaCaixaPadrao;

    /**
     * @Column(name="CRIAR_VOLS_FINAL_CHECKOUT", type="string", nullable=true)
     */
    protected $criarVolsFinalCheckout;

    /**
     * @Column(name="IND_FORCA_EMB_VENDA", type="string", nullable=true)
     */
    protected $forcarEmbVenda;

    /**
     * @Column(name="PRODUTO_EM_INVENTARIO", type="string", nullable=false)
     */
    protected $produtoInventario;

    /**
     * @Column(name="QUEBRA_UNID_FRACIONAVEL", type="string", nullable=false)
     */
    protected $quebraUnidFracionavel;

    /**
     * @Column(name="IND_SEQ_ROTA_PRACA", type="string", nullable=true)
     */
    protected $usaSequenciaRotaPraca;

    /**
     * @Column(name="TIPO_CONF_CARREG", type="string")
     */
    protected $tipoConfCarregamento;

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
     * @return ModeloSeparacaoTipoQuebraFracionado[]
     */
    public function getTiposQuebraFracionado()
    {
        return $this->tiposQuebraFracionado;
    }

    /**
     * @param ModeloSeparacaoTipoQuebraFracionado[] $tiposQuebraFracionado
     */
    public function setTiposQuebraFracionado($tiposQuebraFracionado)
    {
        $this->tiposQuebraFracionado = $tiposQuebraFracionado;
    }

    /**
     * @return ModeloSeparacaoTipoQuebraNaoFracionado[]
     */
    public function getTiposQuebraNaoFracionado()
    {
        return $this->tiposQuebraNaoFracionado;
    }

    /**
     * @param ModeloSeparacaoTipoQuebraNaoFracionado[] $tiposQuebraNaoFracionado
     */
    public function setTiposQuebraNaoFracionado($tiposQuebraNaoFracionado)
    {
        $this->tiposQuebraNaoFracionado = $tiposQuebraNaoFracionado;
    }

    /**
     * @return ModeloSeparacaoTipoQuebraEmbalado[]
     */
    public function getTiposQuebraEmbalado()
    {
        return $this->tiposQuebraEmbalado;
    }

    /**
     * @param ModeloSeparacaoTipoQuebraEmbalado[] $tiposQuebraEmbalado
     */
    public function setTiposQuebraEmbalado($tiposQuebraEmbalado)
    {
        $this->tiposQuebraEmbalado = $tiposQuebraEmbalado;
    }

    /**
     * @return mixed
     */
    public function getImprimeEtiquetaVolume()
    {
        return $this->imprimeEtiquetaVolume;
    }

    /**
     * @param mixed $imprimeEtiquetaVolume
     */
    public function setImprimeEtiquetaVolume($imprimeEtiquetaVolume)
    {
        $this->imprimeEtiquetaVolume = $imprimeEtiquetaVolume;
    }

    /**
     * @return mixed
     */
    public function getSeparacaoPC()
    {
        return $this->separacaoPC;
    }

    /**
     * @param mixed $separacaoPC
     */
    public function setSeparacaoPC($separacaoPC)
    {
        $this->separacaoPC = $separacaoPC;
    }

    /**
     * @return mixed
     */
    public function getUtilizaVolumePatrimonio()
    {
        return $this->utilizaVolumePatrimonio;
    }

    /**
     * @param mixed $utilizaVolumePatrimonio
     */
    public function setUtilizaVolumePatrimonio($utilizaVolumePatrimonio)
    {
        $this->utilizaVolumePatrimonio = $utilizaVolumePatrimonio;
    }

    /**
     * @param mixed $tipoSeparacaoFracionadoEmbalado
     */
    public function setTipoSeparacaoFracionadoEmbalado($tipoSeparacaoFracionadoEmbalado)
    {
        $this->tipoSeparacaoFracionadoEmbalado = $tipoSeparacaoFracionadoEmbalado;
    }

    /**
     * @return mixed
     */
    public function getTipoSeparacaoFracionadoEmbalado()
    {
        return $this->tipoSeparacaoFracionadoEmbalado;
    }

    /**
     * @param mixed $tipoSeparacaoNaoFracionadoEmbalado
     */
    public function setTipoSeparacaoNaoFracionadoEmbalado($tipoSeparacaoNaoFracionadoEmbalado)
    {
        $this->tipoSeparacaoNaoFracionadoEmbalado = $tipoSeparacaoNaoFracionadoEmbalado;
    }

    /**
     * @return mixed
     */
    public function getTipoSeparacaoNaoFracionadoEmbalado()
    {
        return $this->tipoSeparacaoNaoFracionadoEmbalado;
    }

    /**
     * @return mixed
     */
    public function getAgrupContEtiquetas()
    {
        return $this->agrupContEtiquetas;
    }

    /**
     * @param mixed $agrupContEtiquetas
     */
    public function setAgrupContEtiquetas($agrupContEtiquetas)
    {
        $this->agrupContEtiquetas = $agrupContEtiquetas;
    }

    /**
     * @return mixed
     */
    public function getTipoAgroupSeqEtiquetas()
    {
        return $this->tipoAgroupSeqEtiquetas;
    }

    /**
     * @param mixed $tipoAgroupSeqEtiquetas
     */
    public function setTipoAgroupSeqEtiquetas($tipoAgroupSeqEtiquetas)
    {
        $this->tipoAgroupSeqEtiquetas = $tipoAgroupSeqEtiquetas;
    }

    /**
     * @return mixed
     */
    public function getUsaCaixaPadrao()
    {
        return $this->usaCaixaPadrao;
    }

    /**
     * @param mixed $usaCaixaPadrao
     */
    public function setUsaCaixaPadrao($usaCaixaPadrao)
    {
        $this->usaCaixaPadrao = $usaCaixaPadrao;
    }

    /**
     * @return mixed
     */
    public function getCriarVolsFinalCheckout()
    {
        return $this->criarVolsFinalCheckout;
    }

    /**
     * @param mixed $criarVolsFinalCheckout
     */
    public function setCriarVolsFinalCheckout($criarVolsFinalCheckout)
    {
        $this->criarVolsFinalCheckout = $criarVolsFinalCheckout;
    }

    /**
     * @return mixed
     */
    public function getForcarEmbVenda()
    {
        return $this->forcarEmbVenda;
    }

    /**
     * @param mixed $forcarEmbVenda
     */
    public function setForcarEmbVenda($forcarEmbVenda)
    {
        $this->forcarEmbVenda = $forcarEmbVenda;
    }

    /**
     * @return mixed
     */
    public function getProdutoInventario()
    {
        return $this->produtoInventario;
    }

    /**
     * @param mixed $produtoInventario
     */
    public function setProdutoInventario($produtoInventario)
    {
        $this->produtoInventario = $produtoInventario;
    }

    /**
     * @return mixed
     */
    public function getQuebraUnidFracionavel()
    {
        return $this->quebraUnidFracionavel;
    }

    /**
     * @param mixed $quebraUnidFracionavel
     */
    public function setQuebraUnidFracionavel($quebraUnidFracionavel)
    {
        $this->quebraUnidFracionavel = $quebraUnidFracionavel;
    }

    /**
     * @return mixed
     */
    public function getUsaSequenciaRotaPraca()
    {
        return $this->usaSequenciaRotaPraca;
    }

    /**
     * @param mixed $usaSequenciaRotaPraca
     */
    public function setUsaSequenciaRotaPraca($usaSequenciaRotaPraca)
    {
        $this->usaSequenciaRotaPraca = $usaSequenciaRotaPraca;
    }

    /**
     * @return mixed
     */
    public function getTipoConfCarregamento()
    {
        return $this->tipoConfCarregamento;
    }

    /**
     * @param mixed $tipoConfCarregamento
     */
    public function setTipoConfCarregamento($tipoConfCarregamento)
    {
        $this->tipoConfCarregamento = $tipoConfCarregamento;
    }

}