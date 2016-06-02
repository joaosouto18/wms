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
     * Grade do produto
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO")
     * @var \Wms\Domain\Entity\Produto
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
     * @Column(name="DSC_GRADE", type="string", length=10, nullable=false)
     * @var string
     */
    protected $grade;
    /**
     * Quantidade de itens na notaFiscal
     * 
     * @Column(name="QTD_ITEM", type="integer", length=8, nullable=false)
     * @var integer
     */
    protected $quantidade;
    
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

}
