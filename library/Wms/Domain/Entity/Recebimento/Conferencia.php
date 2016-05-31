<?php

namespace Wms\Domain\Entity\Recebimento;

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
     * @var datetime $dataInclusao
     * @Column(name="DTH_CONFERENCIA", type="datetime", nullable=false)
     */
    protected $dataConferencia;

    /**
     * Código qtdConferida conferida
     *  
     * @Column(name="QTD_CONFERIDA", type="integer", nullable=true)
     * @var integer $qtdConferida
     */
    protected $qtdConferida;

    /**
     * Produto
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto", cascade={"persist"})
     * @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO")
     * @var Wms\Domain\Entity\Produto
     */
    protected $produto;


    /**
     * @var string Grade do produto
     * @Column(name="COD_PRODUTO", type="string" , nullable=false)
     */
    protected $codProduto;

    /**
     * @var string Grade do produto
     * @Column(name="DSC_GRADE", type="string", length=10, nullable=false)
     */
    protected $grade;

    /**
     * @var Wms\Domain\Entity\OrdemServico $ordemServico
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS") 
     */
    protected $ordemServico;

    /**
     * Quantidade total - qtdConferida conferida
     *  
     * @Column(name="QTD_DIVERGENCIA", type="integer", nullable=false)
     * @var integer
     */
    protected $qtdDivergencia;

    /**
     * Quantidade avaria
     *  
     * @Column(name="QTD_AVARIA", type="integer", nullable=false)
     * @var integer
     */
    protected $qtdAvaria;

    /**
     * @var Wms\Domain\Entity\Recebimento\Divergencia\Motivo $motivoDivergencia
     * @OneToOne(targetEntity="Wms\Domain\Entity\Recebimento\Divergencia\Motivo")
     * @JoinColumn(name="COD_MOTIVO_DIVER_RECEB", referencedColumnName="COD_MOTIVO_DIVER_RECEB")
     */
    protected $motivoDivergencia;
    
    /**
     * Nota fiscal deste item
     * 
     * @ManyToOne(targetEntity="Wms\Domain\Entity\NotaFiscal")
     * @JoinColumn(name="COD_NOTA_FISCAL", referencedColumnName="COD_NOTA_FISCAL")
     * @var Wms\Domain\Entity\NotaFiscal
     */
    protected $notaFiscal;
    
     /**
     * @var Wms\Domain\Entity\Recebimento $ordemServico
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Recebimento")
     * @JoinColumn(name="COD_RECEBIMENTO", referencedColumnName="COD_RECEBIMENTO") 
     */
    protected $recebimento;

    /**
     * @Column(name="DTH_VALIDADE", type="date")
     * @var date
     */
    protected $dataValidade;

    /**
     * @var string Grade do produto
     * @Column(name="IND_DIVERGENCIA_PESO", type="string", length=10, nullable=false)
     */
    protected $divergenciaPeso;
    
    public function getId()
    {
        return $this->id;
    }

    public function getDataConferencia()
    {
        return $this->dataConferencia;
    }

    public function setDataConferencia($dataConferencia)
    {
        $this->dataConferencia = $dataConferencia;
        return $this;
    }

    public function getQtdConferida()
    {
        return $this->qtdConferida;
    }

    public function setQtdConferida($qtdConferida)
    {
        $this->qtdConferida = (int) $qtdConferida;
        return $this;
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

    public function getQtdDivergencia()
    {
        return $this->qtdDivergencia;
    }

    public function setQtdDivergencia($qtdDivergencia)
    {
        $this->qtdDivergencia = (int) $qtdDivergencia;
        return $this;
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
     * @return date
     */
    public function getDataValidade()
    {
        return $this->dataValidade;
    }

    /**
     * @param date $dataValidade
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
    
}