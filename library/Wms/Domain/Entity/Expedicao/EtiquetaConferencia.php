<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="ETIQUETA_CONFERENCIA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\EtiquetaConferenciaRepository")
 */
class EtiquetaConferencia
{
    const STATUS_PENDENTE_IMPRESSAO = 522;
    const STATUS_ETIQUETA_GERADA = 523;
    const STATUS_PENDENTE_CORTE = 524;
    const STATUS_CORTADO = 525;
    const STATUS_CONFERIDO = 526;
    const STATUS_RECEBIDO_TRANSBORDO = 532;
    const STATUS_EXPEDIDO_TRANSBORDO = 531;

    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_ETIQUETA_CONFERENCIA", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_ETIQUETA_CONFERENCIA_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @Column(name="COD_OS_PRIMEIRA_CONFERENCIA", type="integer", nullable=true)
     */
    protected $codOsPrimeiraConferencia;

    /**
     * @Column(name="COD_OS_SEGUNDA_CONFERENCIA", type="integer", nullable=true)
     */
    protected $codOsSegundaConferencia;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_STATUS", referencedColumnName="COD_SIGLA")
     */
    protected $status;

    /**
     * @Column(name="COD_STATUS", type="integer", nullable=false)
     */
    protected $codStatus;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao")
     * @JoinColumn(name="COD_EXPEDICAO", referencedColumnName="COD_EXPEDICAO")
     */
    protected $expedicao;

    /**
     * @Column(name="COD_EXPEDICAO", type="integer", nullable=false)
     */
    protected $codExpedicao;

    /**
     * @Column(name="COD_PRODUTO", type="integer", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     */
    protected $dscGrade;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     */
    protected $grade;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\Pedido")
     * @JoinColumn(name="COD_PEDIDO", referencedColumnName="COD_PEDIDO")
     */
    protected $pedido;

    /**
     * @Column(name="COD_PEDIDO", type="integer", nullable=false)
     */
    protected $codReferencia;


    /**
     * @Column(name="COD_ETIQUETA_SEPARACAO", type="integer", nullable=false)
     */
    protected $codEtiquetaSeparacao;

    /**
     * @Column(name="COD_OS_TRANSBORDO", type="integer", nullable=true)
     */
    protected $codOSTransbordo;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\VolumePatrimonio")
     * @JoinColumn(name="COD_VOLUME_PATRIMONIO", referencedColumnName="COD_VOLUME_PATRIMONIO")
     */
    protected $volumePatrimonio;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Embalagem")
     * @JoinColumn(name="COD_PRODUTO_EMBALAGEM", referencedColumnName="COD_PRODUTO_EMBALAGEM")
     */
    protected $produtoEmbalagem;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Volume")
     * @JoinColumn(name="COD_PRODUTO_VOLUME", referencedColumnName="COD_PRODUTO_VOLUME")
     */
    protected $produtoVolume;

    /**
     * @Column(name="DTH_CONFERENCIA", type="datetime", nullable=true)
     */
    protected $dataConferencia;

    /**
     * @Column(name="DTH_RECONFERENCIA", type="datetime", nullable=true)
     */
    protected $dataReconferencia;

    /**
     * @Column(name="DTH_CONFERENCIA_TRANSBORDO", type="datetime", nullable=true)
     */
    protected $dataConferenciaTransbordo;


    public function setDataConferencia($dataConferencia)
    {
        $this->dataConferencia = $dataConferencia;
    }

    public function getDataConferencia()
    {
        return $this->dataConferencia;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setPedido($pedido)
    {
        $this->pedido = $pedido;
    }

    public function getPedido()
    {
        return $this->pedido;
    }

    public function setProdutoEmbalagem($produtoEmbalagem)
    {
        $this->produtoEmbalagem = $produtoEmbalagem;
    }

    public function getProdutoEmbalagem()
    {
        return $this->produtoEmbalagem;
    }

    public function setProdutoVolume($produtoVolume)
    {
        $this->produtoVolume = $produtoVolume;
    }

    public function setCodStatus($codStatus)
    {
        $this->codStatus = $codStatus;
    }

    public function getCodStatus()
    {
        return $this->codStatus;
    }

    public function getProdutoVolume()
    {
        return $this->produtoVolume;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    public function getProduto()
    {
        return $this->produto;
    }

    public function setCodEtiquetaSeparacao($codEtiquetaSeparacao)
    {
        $this->codEtiquetaSeparacao = $codEtiquetaSeparacao;
    }

    public function getCodEtiquetaSeparacao()
    {
        return $this->codEtiquetaSeparacao;
    }


    public function setCodExpedicao($codExpedicao)
    {
        $this->codExpedicao = $codExpedicao;
    }

    public function getCodExpedicao()
    {
        return $this->codExpedicao;
    }

    public function setExpedicao($expedicao)
    {
        $this->expedicao = $expedicao;
    }

    public function getExpedicao()
    {
        return $this->expedicao;
    }

    public function setGrade($grade)
    {
        $this->grade = $grade;
    }

    public function getGrade()
    {
        return $this->grade;
    }

    public function setCodOsPrimeiraConferencia($codOsPrimeiraConferencia)
    {
        $this->codOsPrimeiraConferencia = $codOsPrimeiraConferencia;
    }

    public function getCodOsPrimeiraConferencia()
    {
        return $this->codOsPrimeiraConferencia;
    }
    public function setCodOSTransbordo($codOSTransbordo)
    {
        $this->codOSTransbordo = $codOSTransbordo;
    }

    public function getCodOSTransbordo()
    {
        return $this->codOSTransbordo;
    }

    public function getCodProduto()
    {
        return $this->codProduto;
    }

    public function getDscGrade()
    {
        return $this->dscGrade;
    }

    public function setVolumePatrimonio($volumePatrimonio)
    {
        $this->volumePatrimonio = $volumePatrimonio;
    }

    public function getVolumePatrimonio()
    {
        return $this->volumePatrimonio;
    }

    /**
     * @param mixed $dataConferenciaTranbordo
     */
    public function setDataConferenciaTransbordo($dataConferenciaTranbordo)
    {
        $this->dataConferenciaTransbordo = $dataConferenciaTranbordo;
    }

    /**
     * @return mixed
     */
    public function getDataConferenciaTransbordo()
    {
        return $this->dataConferenciaTransbordo;
    }

    /**
     * @return mixed
     */
    public function getCodOsSegundaConferencia()
    {
        return $this->codOsSegundaConferencia;
    }

    /**
     * @param mixed $codOsSegundaConferencia
     */
    public function setCodOsSegundaConferencia($codOsSegundaConferencia)
    {
        $this->codOsSegundaConferencia = $codOsSegundaConferencia;
    }

    /**
     * @return mixed
     */
    public function getDataReconferencia()
    {
        return $this->dataReconferencia;
    }

    /**
     * @param mixed $dataReconferencia
     */
    public function setDataReconferencia($dataReconferencia)
    {
        $this->dataReconferencia = $dataReconferencia;
    }

}