<?php

namespace Wms\Domain\Entity\MapaSeparacao;

/**
 * Modelo de Separação
 *
 * @Table(name="MODELO_SEPARACAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\MapaSeparacao\ModeloSeparacaoRepository")
 */
class ModeloSeparacao
{

    /**
     * @Column(name="COD_MODELO_SEPARACAO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_MODELO_SEPARACAO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="TIPO_SEPARACAO_FRACIONADO", type="string", length=20, nullable=false)
     */
    protected $tipoSeparacaoFracionado;

    /**
     * @Column(name="TIPO_SEPARACAO_NAOFRACIONADO", type="string", length=20, nullable=false)
     */
    protected $tipoSeparacaoNaofracionado;

    /**
     * @Column(name="TIPO_QUEBRA_FRACIONADO", type="string", length=20, nullable=true)
     */
    protected $tipoQuebraFracionado;

    /**
     * @Column(name="TIPO_QUEBRA_NAOFRACIONADO", type="string", length=20, nullable=true)
     */
    protected $tipoQuebraNaofracionado;

    /**
     * @Column(name="QUEBRA_COLETOR", type="string", length=20, nullable=false)
     */
    protected $quebraColetor;

    /**
     * @Column(name="EMITIR_ETIQUETA_MAE", type="string", length=20, nullable=false)
     */
    protected $emitirEtiquetaMae;

    /**
     * @Column(name="EMITIR_ETIQUETA_MAPA", type="string", length=20, nullable=false)
     */
    protected $emitirEtiquetaMapa;

    /**
     * @Column(name="CONVERSAO_FATOR_PRODUTO", type="string", length=20, nullable=false)
     */
    protected $conversaoFatorProduto;

    public function setTipoSeparacaoFracionado($tipoSeparacaoFracionado)
    {
        $this->tipoSeparacaoFracionado = $tipoSeparacaoFracionado;
    }

    public function getTipoSeparacaoFracionado()
    {
        return $this->tipoSeparacaoFracionado;
    }

    public function setTipoSeparacaoNaofracionado($tipoSeparacaoNaofracionado)
    {
        $this->tipoSeparacaoNaofracionado = $tipoSeparacaoNaofracionado;
    }

    public function getTipoSeparacaoNaofracionado()
    {
        return $this->tipoSeparacaoNaofracionado;
    }

    public function setTipoQuebraFracionado($tipoQuebraFracionado)
    {
        $this->tipoQuebraFracionado = $tipoQuebraFracionado;
    }

    public function getTipoQuebraFracionado()
    {
        return $this->tipoQuebraFracionado;
    }

    public function setTipoQuebraNaofracionado($tipoQuebraNaofracionado)
    {
        $this->tipoQuebraNaofracionado = $tipoQuebraNaofracionado;
    }

    public function getTipoQuebraNaofracionado()
    {
        return $this->tipoQuebraNaofracionado;
    }

    public function setQuebraColetor($quebraColetor)
    {
        $this->quebraColetor = $quebraColetor;
    }

    public function getQuebraColetor()
    {
        return $this->quebraColetor;
    }

    public function setEmitirEtiquetaMae($emitirEtiquetaMae)
    {
        $this->emitirEtiquetaMae = $emitirEtiquetaMae;
    }

    public function getEmitirEtiquetaMae()
    {
        return $this->emitirEtiquetaMae;
    }

    public function setEmitirEtiquetaMapa($emitirEtiquetaMapa)
    {
        $this->emitirEtiquetaMapa = $emitirEtiquetaMapa;
    }

    public function getEmitirEtiquetaMapa()
    {
        return $this->emitirEtiquetaMapa;
    }

    public function setConversaoFatorProduto($conversaoFatorProduto)
    {
        $this->conversaoFatorProduto = $conversaoFatorProduto;
    }

    public function getConversaoFatorProduto()
    {
        return $this->conversaoFatorProduto;
    }

}