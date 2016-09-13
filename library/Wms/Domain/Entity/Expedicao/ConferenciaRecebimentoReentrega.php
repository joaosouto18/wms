<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="CONF_RECEB_REENTREGA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\ConferenciaRecebimentoReentregaRepository")
 */
class ConferenciaRecebimentoReentrega
{

    /**
     * @Id
     * @Column(name="COD_CONF_RECEB_REENTREGA", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_CONF_RECEB_REENTREGA_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\RecebimentoReentrega")
     * @JoinColumn(name="COD_RECEBIMENTO_REENTREGA", referencedColumnName="COD_RECEBIMENTO_REENTREGA")
     */
    protected $recebimentoReentrega;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Volume")
     * @JoinColumn(name="COD_PRODUTO_VOLUME", referencedColumnName="COD_PRODUTO_VOLUME")
     */
    protected $produtoVolume;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Embalagem")
     * @JoinColumn(name="COD_PRODUTO_EMBALAGEM", referencedColumnName="COD_PRODUTO_EMBALAGEM")
     */
    protected $produtoEmbalagem;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     * @var Wms\Domain\Entity\Produto $produto Produto que o volumes está relacionado a
     */
    protected $produto;

    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     * @var string Código do produto
     */
    protected $codProduto;

    /**
     * @var string Grade do produto
     * @Column(name="DSC_GRADE", type="string", length=10, nullable=false)
     */
    protected $grade;

    /**
     * @Column(name="QTD_CONFERIDA", type="decimal")
     * @var int
     */
    protected $quantidadeConferida;

    /**
     * @Column(name="QTD_EMBALAGEM_CONFERIDA", type="decimal")
     * @var int
     */
    protected $qtdEmbalagemConferida;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS")
     */
    protected $ordemServico;

    /**
     * @Column(name="NUM_CONFERENCIA", type="integer")
     * @var int
     */
    protected $numeroConferencia;

    /**
     * @Column(name="DTH_CONFERENCIA", type="date")
     * @var string
     */
    protected $dataConferencia;

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
    public function getRecebimentoReentrega()
    {
        return $this->recebimentoReentrega;
    }

    /**
     * @param mixed $recebimentoReentrega
     */
    public function setRecebimentoReentrega($recebimentoReentrega)
    {
        $this->recebimentoReentrega = $recebimentoReentrega;
    }

    /**
     * @return mixed
     */
    public function getProdutoVolume()
    {
        return $this->produtoVolume;
    }

    /**
     * @param mixed $produtoVolume
     */
    public function setProdutoVolume($produtoVolume)
    {
        $this->produtoVolume = $produtoVolume;
    }

    /**
     * @return mixed
     */
    public function getProdutoEmbalagem()
    {
        return $this->produtoEmbalagem;
    }

    /**
     * @param mixed $produtoEmbalagem
     */
    public function setProdutoEmbalagem($produtoEmbalagem)
    {
        $this->produtoEmbalagem = $produtoEmbalagem;
    }

    /**
     * @return Wms\Domain\Entity\Produto
     */
    public function getProduto()
    {
        return $this->produto;
    }

    /**
     * @param Wms\Domain\Entity\Produto $produto
     */
    public function setProduto($produto)
    {
        $this->produto = $produto;
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
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * @param string $grade
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
    }

    /**
     * @return int
     */
    public function getQuantidadeConferida()
    {
        return $this->quantidadeConferida;
    }

    /**
     * @param int $quantidadeConferida
     */
    public function setQuantidadeConferida($quantidadeConferida)
    {
        $this->quantidadeConferida = $quantidadeConferida;
    }

    /**
     * @return int
     */
    public function getQtdEmbalagemConferida()
    {
        return $this->qtdEmbalagemConferida;
    }

    /**
     * @param int $qtdEmbalagemConferida
     */
    public function setQtdEmbalagemConferida($qtdEmbalagemConferida)
    {
        $this->qtdEmbalagemConferida = $qtdEmbalagemConferida;
    }

    /**
     * @return mixed
     */
    public function getOrdemServico()
    {
        return $this->ordemServico;
    }

    /**
     * @param mixed $ordemServico
     */
    public function setOrdemServico($ordemServico)
    {
        $this->ordemServico = $ordemServico;
    }

    /**
     * @return int
     */
    public function getNumeroConferencia()
    {
        return $this->numeroConferencia;
    }

    /**
     * @param int $numeroConferencia
     */
    public function setNumeroConferencia($numeroConferencia)
    {
        $this->numeroConferencia = $numeroConferencia;
    }

    /**
     * @return string
     */
    public function getDataConferencia()
    {
        return $this->dataConferencia;
    }

    /**
     * @param string $dataConferencia
     */
    public function setDataConferencia($dataConferencia)
    {
        $this->dataConferencia = $dataConferencia;
    }
}