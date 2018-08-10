<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="MAPA_SEPARACAO_CONFERENCIA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\MapaSeparacaoConferenciaRepository")
 */
class MapaSeparacaoConferencia
{

    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_MAPA_SEPARACAO_CONFERENCIA", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_MAPA_SEPARACAO_CONF_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     */
    protected $dscGrade;

    /**
     * @Column(name="COD_PRODUTO_EMBALAGEM", type="integer", nullable=true)
     */
    protected $codProdutoEmbalagem;

    /**
     * @Column(name="COD_PRODUTO_VOLUME", type="integer", nullable=true)
     */
    protected $codProdutoVolume;

    /**
     * @Column(name="QTD_EMBALAGEM", type="decimal", nullable=false)
     */
    protected $qtdEmbalagem;

    /**
     * @Column(name="QTD_CONFERIDA", type="decimal", nullable=false)
     */
    protected $qtdConferida;

    /**
     * @Column(name="COD_OS", type="integer", nullable=true)
     */
    protected $codOS;

    /**
     * @Column(name="NUM_CONFERENCIA", type="integer", nullable=true)
     */
    protected $numConferencia;

    /**
     * @Column(name="IND_CONFERENCIA_FECHADA", type="integer", nullable=true)
     */
    protected $indConferenciaFechada;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\VolumePatrimonio")
     * @JoinColumn(name="COD_VOLUME_PATRIMONIO", referencedColumnName="COD_VOLUME_PATRIMONIO")
     */
    protected $volumePatrimonio;

    /**
     * @Column(name="DTH_CONFERENCIA", type="datetime", nullable=true)
     */
    protected $dataConferencia;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbalado")
     * @JoinColumn(name="COD_MAPA_SEPARACAO_EMBALADO",referencedColumnName="COD_MAPA_SEPARACAO_EMB_CLIENTE")
     */
    protected $mapaSeparacaoEmbalado;

    /**
     * @Column(name="COD_PESSOA", type="integer", nullable=true)
     */
    protected $codPessoa;

    /**
     * @Column(name="COD_MAPA_SEPARACAO", type="integer", nullable=true)
     */
    protected $codMapaSeparacao;

    /**
     * @var string
     * @Column(name="DSC_LOTE", type="string", nullable=true)
     */
    protected $lote;

    /**
     * @param mixed $codOS
     */
    public function setCodOS($codOS)
    {
        $this->codOS = $codOS;
    }

    /**
     * @return mixed
     */
    public function getCodOS()
    {
        return $this->codOS;
    }

    /**
     * @param mixed $codProduto
     */
    public function setCodProduto($codProduto)
    {
        $this->codProduto = $codProduto;
    }

    /**
     * @return mixed
     */
    public function getCodProduto()
    {
        return $this->codProduto;
    }

    /**
     * @param mixed $dscGrade
     */
    public function setDscGrade($dscGrade)
    {
        $this->dscGrade = $dscGrade;
    }

    /**
     * @return mixed
     */
    public function getDscGrade()
    {
        return $this->dscGrade;
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
     * @param mixed $indConferenciaFechada
     */
    public function setIndConferenciaFechada($indConferenciaFechada)
    {
        $this->indConferenciaFechada = $indConferenciaFechada;
    }

    /**
     * @return mixed
     */
    public function getIndConferenciaFechada()
    {
        return $this->indConferenciaFechada;
    }

    /**
     * @param mixed $numConferencia
     */
    public function setNumConferencia($numConferencia)
    {
        $this->numConferencia = $numConferencia;
    }

    /**
     * @return mixed
     */
    public function getNumConferencia()
    {
        return $this->numConferencia;
    }

    /**
     * @param mixed $qtdConferida
     */
    public function setQtdConferida($qtdConferida)
    {
        $this->qtdConferida = $qtdConferida;
    }

    /**
     * @return mixed
     */
    public function getQtdConferida()
    {
        return $this->qtdConferida;
    }

    /**
     * @param mixed $qtdEmbalagem
     */
    public function setQtdEmbalagem($qtdEmbalagem)
    {
        $this->qtdEmbalagem = $qtdEmbalagem;
    }

    /**
     * @return mixed
     */
    public function getQtdEmbalagem()
    {
        return $this->qtdEmbalagem;
    }

    /**
     * @param mixed $dataConferencia
     */
    public function setDataConferencia($dataConferencia)
    {
        $this->dataConferencia = $dataConferencia;
    }

    /**
     * @return mixed
     */
    public function getDataConferencia()
    {
        return $this->dataConferencia;
    }

    /**
     * @param mixed $volumePatrimonio
     */
    public function setVolumePatrimonio($volumePatrimonio)
    {
        $this->volumePatrimonio = $volumePatrimonio;
    }

    /**
     * @return mixed
     */
    public function getVolumePatrimonio()
    {
        return $this->volumePatrimonio;
    }

    /**
     * @return mixed
     */
    public function getMapaSeparacaoEmbalado()
    {
        return $this->mapaSeparacaoEmbalado;
    }

    /**
     * @param mixed $mapaSeparacaoEmbalado
     */
    public function setMapaSeparacaoEmbalado($mapaSeparacaoEmbalado)
    {
        $this->mapaSeparacaoEmbalado = $mapaSeparacaoEmbalado;
    }

    /**
     * @return mixed
     */
    public function getCodPessoa()
    {
        return $this->codPessoa;
    }

    /**
     * @param mixed $codPessoa
     */
    public function setCodPessoa($codPessoa)
    {
        $this->codPessoa = $codPessoa;
    }

    /**
     * @param mixed $codProdutoEmbalagem
     */
    public function setCodProdutoEmbalagem($codProdutoEmbalagem)
    {
        $this->codProdutoEmbalagem = $codProdutoEmbalagem;
    }

    /**
     * @return mixed
     */
    public function getCodProdutoEmbalagem()
    {
        return $this->codProdutoEmbalagem;
    }

    /**
     * @param mixed $codProdutoVolume
     */
    public function setCodProdutoVolume($codProdutoVolume)
    {
        $this->codProdutoVolume = $codProdutoVolume;
    }

    /**
     * @return mixed
     */
    public function getCodProdutoVolume()
    {
        return $this->codProdutoVolume;
    }

    /**
     * @param mixed $codMapaSeparacao
     */
    public function setCodMapaSeparacao($codMapaSeparacao)
    {
        $this->codMapaSeparacao = $codMapaSeparacao;
    }

    /**
     * @return mixed
     */
    public function getCodMapaSeparacao()
    {
        return $this->codMapaSeparacao;
    }

    /**
     * @return string
     */
    public function getLote()
    {
        return $this->lote;
    }

    /**
     * @param string $lote
     */
    public function setLote($lote)
    {
        $this->lote = $lote;
    }

}