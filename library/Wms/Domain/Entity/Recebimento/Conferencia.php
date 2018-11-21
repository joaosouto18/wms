<?php

namespace Wms\Domain\Entity\Recebimento;
use Wms\Domain\Entity\Produto;

/**
 * Conferencia da carga
 *
 * @Table(name="RECEBIMENTO_CONFERENCIA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Recebimento\ConferenciaRepository")
 */
class Conferencia
{

    /**
     * @var integer $id
     *
     * @Column(name="COD_RECEBIMENTO_CONFERENCIA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RECEBIMENTO_CONFERENCIA_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * Data e hora iniciou ou recebimento
     * 
     * @var \DateTime $dataInclusao
     * @Column(name="DTH_CONFERENCIA", type="datetime", nullable=false)
     */
    protected $dataConferencia;

    /**
     * Código qtdConferida conferida
     *  
     * @Column(name="QTD_CONFERIDA", type="decimal", nullable=true)
     */
    protected $qtdConferida;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     * @var Produto
     */
    protected $produto;


    /**
     * @var string Grade do produto
     * @Column(name="COD_PRODUTO", type="string" , nullable=false)
     */
    protected $codProduto;

    /**
     * @var string Grade do produto
     * @Column(name="DSC_GRADE", type="string", length=255, nullable=false)
     */
    protected $grade;

    /**
     * @var \Wms\Domain\Entity\OrdemServico $ordemServico
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS") 
     */
    protected $ordemServico;

    /**
     * Quantidade total - qtdConferida conferida
     *  
     * @Column(name="QTD_DIVERGENCIA", type="decimal", nullable=false)
     */
    protected $qtdDivergencia;

    /**
     * Quantidade avaria
     *  
     * @Column(name="QTD_AVARIA", type="decimal", nullable=false)
     */
    protected $qtdAvaria;

    /**
     * @var \Wms\Domain\Entity\Recebimento\Divergencia\Motivo $motivoDivergencia
     * @OneToOne(targetEntity="Wms\Domain\Entity\Recebimento\Divergencia\Motivo")
     * @JoinColumn(name="COD_MOTIVO_DIVER_RECEB", referencedColumnName="COD_MOTIVO_DIVER_RECEB")
     */
    protected $motivoDivergencia;
    
    /**
     * Nota fiscal deste item
     * 
     * @ManyToOne(targetEntity="Wms\Domain\Entity\NotaFiscal")
     * @JoinColumn(name="COD_NOTA_FISCAL", referencedColumnName="COD_NOTA_FISCAL")
     * @var \Wms\Domain\Entity\NotaFiscal
     */
    protected $notaFiscal;
    
     /**
     * @var \Wms\Domain\Entity\Recebimento $ordemServico
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Recebimento")
     * @JoinColumn(name="COD_RECEBIMENTO", referencedColumnName="COD_RECEBIMENTO") 
     */
    protected $recebimento;

    /**
     * @Column(name="DTH_VALIDADE", type="date")
     * @var \DateTime
     */
    protected $dataValidade;

    /**
     * @var string Grade do produto
     * @Column(name="IND_DIVERGENCIA_PESO", type="string", length=10, nullable=false)
     */
    protected $divergenciaPeso;

    /**
     * @Column(name="NUM_PECAS", type="integer")
     * @var integer
     */
    protected $numPecas;

    /**
     * @var string
     * @Column(name="IND_DIVERG_VOLUMES", type="string", length=1, nullable=false)
     */
    protected $indDivergVolumes;

    /**
     * @var string
     * @Column(name="IND_DIVERG_LOTE", type="string", length=1, nullable=false)
     */
    protected $indDivergLote;

    /**
     * @var string
     * @Column(name="DSC_LOTE", type="string", length=1, nullable=false)
     */
    protected $lote;

    /**
     * @return string
     */
    public function getIndDivergLote()
    {
        return $this->indDivergLote;
    }

    /**
     * @param string $indDivergLote
     */
    public function setIndDivergLote($indDivergLote)
    {
        $this->indDivergLote = $indDivergLote;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDataConferencia()
    {
        return $this->dataConferencia;
    }

    /**
     * @param $dataConferencia
     * @return $this
     */
    public function setDataConferencia($dataConferencia)
    {
        $this->dataConferencia = $dataConferencia;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getQtdConferida()
    {
        return $this->qtdConferida;
    }

    /**
     * @param mixed $qtdConferida
     */
    public function setQtdConferida($qtdConferida)
    {
        $this->qtdConferida = $qtdConferida;
    }

    public function getProduto()
    {
        return $this->produto;
    }

    public function setProduto($produto)
    {
        $this->produto = $produto;
        return $this;
    }

    public function getGrade()
    {
        return $this->grade;
    }

    public function setGrade($grade)
    {
        $this->grade = $grade;
        return $this;
    }

    public function getOrdemServico()
    {
        return $this->ordemServico;
    }

    public function setOrdemServico($ordemServico)
    {
        $this->ordemServico = $ordemServico;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getQtdDivergencia()
    {
        return $this->qtdDivergencia;
    }

    /**
     * @param mixed $qtdDivergencia
     */
    public function setQtdDivergencia($qtdDivergencia)
    {
        $this->qtdDivergencia = $qtdDivergencia;
    }

    public function getQtdAvaria()
    {
        return $this->qtdAvaria;
    }

    public function setQtdAvaria($qtdAvaria)
    {
        $this->qtdAvaria = (int) $qtdAvaria;
        return $this;
    }
    
    public function getMotivoDivergencia()
    {
        return $this->motivoDivergencia;
    }

    public function setMotivoDivergencia($motivoDivergencia = null)
    {
        $this->motivoDivergencia = $motivoDivergencia;
        return $this;
    }

    public function getNotaFiscal()
    {
        return $this->notaFiscal;
    }

    public function setNotaFiscal($notaFiscal = null)
    {
        $this->notaFiscal = $notaFiscal;
        return $this;
    }
    
    public function getRecebimento()
    {
        return $this->recebimento;
    }

    public function setRecebimento($recebimento)
    {
        $this->recebimento = $recebimento;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDataValidade()
    {
        return $this->dataValidade;
    }

    /**
     * @param \DateTime $dataValidade
     */
    public function setDataValidade($dataValidade)
    {
        $this->dataValidade = $dataValidade;
    }

    /**
     * @return string
     */
    public function getCodProduto()
    {
        return $this->codProduto;
    }

    /**
     * @param string $codProduto
     */
    public function setCodProduto($codProduto)
    {
        $this->codProduto = $codProduto;
    }

    /**
     * @return string
     */
    public function getDivergenciaPeso()
    {
        return $this->divergenciaPeso;
    }

    /**
     * @param string $divergenciaPeso
     */
    public function setDivergenciaPeso($divergenciaPeso)
    {
        $this->divergenciaPeso = $divergenciaPeso;
    }

    /**
     * @return int
     */
    public function getNumPecas()
    {
        return $this->numPecas;
    }

    /**
     * @param int $numPecas
     */
    public function setNumPecas($numPecas)
    {
        $this->numPecas = $numPecas;
    }

    /**
     * @return string
     */
    public function getIndDivergVolumes()
    {
        return $this->indDivergVolumes;
    }

    /**
     * @param string $indDivergVolumes
     */
    public function setIndDivergVolumes($indDivergVolumes)
    {
        $this->indDivergVolumes = $indDivergVolumes;
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