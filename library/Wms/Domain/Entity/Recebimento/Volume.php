<?php

namespace Wms\Domain\Entity\Recebimento;

/**
 * Recebimento Embalagem
 *
 * @Table(name="RECEBIMENTO_VOLUME")
 * @Entity(repositoryClass="Wms\Domain\Entity\Recebimento\VolumeRepository")
 */
class Volume
{

    /**
     * @Id
     * @Column(name="COD_RECEBIMENTO_VOLUME", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RECEBIMENTO_VOLUME_01", allocationSize=1, initialValue=1)
     * @var integer Código do volume do recebimento
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
     * @var \Wms\Domain\Entity\OrdemServico $ordemServico
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS") 
     */
    protected $ordemServico;

    /**
     * @var \Wms\Domain\Entity\Recebimento $recebimento
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Recebimento")
     * @JoinColumn(name="COD_RECEBIMENTO", referencedColumnName="COD_RECEBIMENTO") 
     */
    protected $recebimento;

    /**
 * @var \Wms\Domain\Entity\Produto\Volume $volume
 * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Volume")
 * @JoinColumn(name="COD_PRODUTO_VOLUME", referencedColumnName="COD_PRODUTO_VOLUME")
 */
    protected $volume;

    /**
     * Norma de paletizacao do recebimento
     *
     * @OneToOne(targetEntity="Wms\Domain\Entity\Produto\NormaPaletizacao")
     * @JoinColumn(name="COD_NORMA_PALETIZACAO", referencedColumnName="COD_NORMA_PALETIZACAO")
     * @var \Wms\Domain\Entity\Produto\NormaPaletizacao $normaPaletizacao
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
     * @Column(name="QTD_CONFERIDA_BLOQUEADA", type="float")
     * @var float
     */
    protected $qtdBloqueada;

    /**
     * @Column(name="DSC_LOTE", type="string")
     * @var string
     */
    protected $lote;

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

    /**
     * @return \Wms\Domain\Entity\Produto\Volume
     */
    public function getVolume()
    {
        return $this->volume;
    }

    public function setVolume($volume)
    {
        $this->volume = $volume;
        return $this;
    }

    /**
     * @param \Wms\Domain\Entity\Produto\NormaPaletizacao $normaPaletizacao
     */
    public function setNormaPaletizacao($normaPaletizacao)
    {
        $this->normaPaletizacao = $normaPaletizacao;
    }

    /**
     * @return \Wms\Domain\Entity\Produto\NormaPaletizacao
     */
    public function getNormaPaletizacao()
    {
        return $this->normaPaletizacao;
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
     * @return mixed
     */
    public function getQtdBloqueada()
    {
        return $this->qtdBloqueada;
    }

    /**
     * @param mixed $qtdBloqueada
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