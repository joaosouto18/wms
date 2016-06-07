<?php

namespace Wms\Domain\Entity\Recebimento;

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
     * @var Wms\Domain\Entity\OrdemServico $ordemServico
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS") 
     */
    protected $ordemServico;

    /**
     * @var Wms\Domain\Entity\Recebimento $recebimento
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Recebimento")
     * @JoinColumn(name="COD_RECEBIMENTO", referencedColumnName="COD_RECEBIMENTO") 
     */
    protected $recebimento;

    /**
     * @var Wms\Domain\Entity\Produto\Embalagem $embalagem
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Embalagem", inversedBy="recebimentoEmbalagens")
     * @JoinColumn(name="COD_PRODUTO_EMBALAGEM", referencedColumnName="COD_PRODUTO_EMBALAGEM") 
     */
    protected $embalagem;

    /**
     * Norma de paletizacao do recebimento
     *
     * @OneToOne(targetEntity="Wms\Domain\Entity\Produto\NormaPaletizacao")
     * @JoinColumn(name="COD_NORMA_PALETIZACAO", referencedColumnName="COD_NORMA_PALETIZACAO")
     * @var Wms\Domain\Entity\Produto\NormaPaletizacao $normaPaletizacao
     */
    protected $normaPaletizacao;

    /**
     * @Column(name="DTH_VALIDADE", type="date")
     * @var date
     */
    protected $dataValidade;

    /**
     * @Column(name="NUM_PESO", type="float")
     * @var float
     */
    protected $numPeso;

    public function setNormaPaletizacao($normaPaletizacao)
    {
        $this->normaPaletizacao = $normaPaletizacao;
    }

    /**
     * @return \Wms\Domain\Entity\Recebimento\Wms\Domain\Entity\Produto\NormaPaletizacao
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
        $this->qtdConferida = (int) $qtdConferida;
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

    
}