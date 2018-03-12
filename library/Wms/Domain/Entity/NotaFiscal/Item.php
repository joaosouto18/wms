<?php

namespace Wms\Domain\Entity\NotaFiscal;

/**
 * Nota fiscal
 *
 * @Table(name="NOTA_FISCAL_ITEM")
 * @Entity(repositoryClass="Bisna\Base\Domain\Entity\Repository")
 */
class Item
{

    /**
     * Código da notaFiscal
     * 
     * @Id
     * @Column(name="COD_NOTA_FISCAL_ITEM", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_NOTA_FISCAL_ITEM_01", allocationSize=1, initialValue=1)
     * @var integer
     */
    protected $id;
    /**
     * Nota fiscal deste item
     * 
     * @ManyToOne(targetEntity="Wms\Domain\Entity\NotaFiscal")
     * @JoinColumn(name="COD_NOTA_FISCAL", referencedColumnName="COD_NOTA_FISCAL")
     * @var \Wms\Domain\Entity\NotaFiscal
     */
    protected $notaFiscal;
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
     * Grade do produto
     *
     * @Column(name="COD_PRODUTO", type="string",  nullable=false)
     * @var string
     */
    protected $codProduto;
    /**
     * Grade do produto
     * 
     * @Column(name="DSC_GRADE", type="string", length=255, nullable=false)
     * @var string
     */
    protected $grade;
    /**
     * Quantidade de itens na notaFiscal
     * 
     * @Column(name="QTD_ITEM", type="decimal", length=8, nullable=false)
     * @var integer
     */
    protected $quantidade;

    /**
     * Peso da nota fiscal
     *
     * @Column(name="NUM_PESO", type="float", nullable=true)
     * @var float
     */
    protected $numPeso;
    
    public function getId()
    {
	return $this->id;
    }
    
    public function getNotaFiscal()
    {
        return $this->notaFiscal;
    }

    public function setNotaFiscal($notaFiscal)
    {
        $this->notaFiscal = $notaFiscal;
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

    public function getQuantidade()
    {
        return $this->quantidade;
    }

    public function setQuantidade($quantidade)
    {
        $this->quantidade = $quantidade;
        return $this;
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
