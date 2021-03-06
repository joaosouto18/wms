<?php

namespace Wms\Domain\Entity\Recebimento;
use Wms\Domain\Entity\Produto\NormaPaletizacao;
use Wms\Domain\Entity\Recebimento;

/**
 * Recebimento Embalagem
 *
 * @Table(name="RECEBIMENTO_EMBALAGEM")
 * @Entity(repositoryClass="Wms\Domain\Entity\Recebimento\EmbalagemRepository")
 */
class Embalagem
{

    /**
     * @Id
     * @Column(name="COD_RECEBIMENTO_EMBALAGEM", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RECEBIMENTO_EMBALAGEM_01", allocationSize=1, initialValue=1)
     * @var integer Código da embalagem da OS
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
     * @Column(name="QTD_CONFERIDA", type="float", nullable=true)
     */
    protected $qtdConferida;

    /**
     * @var \Wms\Domain\Entity\OrdemServico $ordemServico
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS") 
     */
    protected $ordemServico;

    /**
     * @var Recebimento $recebimento
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Recebimento")
     * @JoinColumn(name="COD_RECEBIMENTO", referencedColumnName="COD_RECEBIMENTO") 
     */
    protected $recebimento;

    /**
     * @var \Wms\Domain\Entity\Produto\Embalagem $embalagem
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Embalagem", inversedBy="recebimentoEmbalagens")
     * @JoinColumn(name="COD_PRODUTO_EMBALAGEM", referencedColumnName="COD_PRODUTO_EMBALAGEM") 
     */
    protected $embalagem;

    /**
     * @Column(name="QTD_EMBALAGEM", type="decimal", length=60, nullable=false)
     */
    protected $qtdEmbalagem;

    /**
     * Norma de paletizacao do recebimento
     *
     * @OneToOne(targetEntity="Wms\Domain\Entity\Produto\NormaPaletizacao")
     * @JoinColumn(name="COD_NORMA_PALETIZACAO", referencedColumnName="COD_NORMA_PALETIZACAO")
     * @var NormaPaletizacao $normaPaletizacao
     */
    protected $normaPaletizacao;

    /**
     * @Column(name="DTH_VALIDADE", type="date")
     * @var \DateTime
     */
    protected $dataValidade;

    /**
     * @Column(name="NUM_PESO", type="float")
     * @var float
     */
    protected $numPeso;

    /**
     * @Column(name="NUM_PECAS", type="integer")
     * @var integer
     */
    protected $numPecas;

    /**
     * @Column(name="QTD_CONFERIDA_BLOQUEADA", type="float")
     * @var float
     */
    protected $qtdBloqueada;

    /**
     * @Column(name="DSC_LOTE", type="string")
     * @var string
     */
    protected $lote;

    /**
     * @param NormaPaletizacao $normaPaletizacao
     */
    public function setNormaPaletizacao($normaPaletizacao)
    {
        $this->normaPaletizacao = $normaPaletizacao;
    }

    /**
     * @return NormaPaletizacao
     */
    public function getNormaPaletizacao()
    {
        return $this->normaPaletizacao;
    }

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
        $this->qtdConferida = $qtdConferida;
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

    public function getRecebimento()
    {
        return $this->recebimento;
    }

    public function setRecebimento($recebimento)
    {
        $this->recebimento = $recebimento;
        return $this;
    }

    public function getEmbalagem()
    {
        return $this->embalagem;
    }

    public function setEmbalagem($embalagem)
    {
        $this->embalagem = $embalagem;
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
     * @return float
     */
    public function getNumPeso()
    {
        return $this->numPeso;
    }

    /**
     * @param float $numPeso
     */
    public function setNumPeso($numPeso)
    {
        $this->numPeso = $numPeso;
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
     * @return float
     */
    public function getQtdBloqueada()
    {
        return $this->qtdBloqueada;
    }

    /**
     * @param float $qtdBloqueada
     */
    public function setQtdBloqueada($qtdBloqueada)
    {
        $this->qtdBloqueada = $qtdBloqueada;
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