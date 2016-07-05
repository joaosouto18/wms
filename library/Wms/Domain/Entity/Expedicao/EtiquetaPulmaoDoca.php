<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="ETIQUETA_PULMAO_DOCA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\EtiquetaPulmaoDocaRepository")
 */
class EtiquetaPulmaoDoca
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
     * @Column(name="COD_ETIQUETA_PULMAO_DOCA", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_ETIQUETA_PULMAO_DOCA_01", initialValue=1, allocationSize=1)
     */
    protected $id;

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
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
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
     * @Column(name="DSC_REIMPRESSAO", type="string", nullable=true)
     */
    protected $reimpressao;

    /**
     * @Column(name="COD_OS", type="integer", nullable=true)
     */
    protected $codOS;

    /**
     * @Column(name="COD_OS_TRANSBORDO", type="integer", nullable=true)
     */
    protected $codOSTransbordo;



    /**
     * @Column(name="COD_CLIENTE", type="integer", nullable=true)
     */
    protected $codCliente;

    /**
     * @Column(name="COD_PRACA", type="integer", nullable=true)
     */
    protected $codPraca;

    /**
     * @Column(name="DTH_CONFERENCIA", type="datetime", nullable=true)
     */
    protected $dataConferencia;


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

    public function setCodStatus($codStatus)
    {
        $this->codStatus = $codStatus;
    }

    public function getCodStatus()
    {
        return $this->codStatus;
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

    public function setGrade($grade)
    {
        $this->grade = $grade;
    }

    public function getGrade()
    {
        return $this->grade;
    }

    public function setReimpressao($reimpressao)
    {
        $this->reimpressao = $reimpressao;
    }

    public function getReimpressao()
    {
        return $this->reimpressao;
    }


    public function setCodOS($codOS)
    {
        $this->codOS = $codOS;
    }

    public function getCodOS()
    {
        return $this->codOS;
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

    /**
     * @param mixed $codCliente
     */
    public function setCodCliente($codCliente)
    {
        $this->codCliente = $codCliente;
    }

    /**
     * @return mixed
     */
    public function getCodCliente()
    {
        return $this->codCliente;
    }

    /**
     * @param mixed $codPraca
     */
    public function setCodPraca($codPraca)
    {
        $this->codPraca = $codPraca;
    }

    /**
     * @return mixed
     */
    public function getCodPraca()
    {
        return $this->codPraca;
    }

}