<?php

namespace Wms\Domain\Entity\Inventario;
use Wms\Domain\Entity\Produto;

/**
 * @Table(name="INVENTARIO_ENDERECO_PRODUTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Inventario\EnderecoProdutoRepository")
 */
class EnderecoProduto
{

    /**
     * @var int
     * @Column(name="COD_INVENTARIO_ENDERECO_PRODUTO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_INVENTARIO_ENDERECO_PRODUTO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var Produto
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @var Endereco
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Inventario\Endereco", inversedBy="contagemEndereco")
     * @JoinColumn(name="COD_INVENTARIO_ENDERECO", referencedColumnName="COD_INVENTARIO_ENDERECO")
     */
    protected $inventarioEndereco;

    /**
     * @var string
     * @Column(name="COD_PRODUTO")
     */
    protected $codProduto;

    /**@var string
     * @Column(name="DSC_GRADE")
     */
    protected $grade;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Produto
     */
    public function getProduto()
    {
        return $this->produto;
    }

    /**
     * @param Produto $produto
     */
    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    /**
     * @return Endereco
     */
    public function getInventarioEndereco()
    {
        return $this->inventarioEndereco;
    }

    /**
     * @param Endereco $inventarioEndereco
     */
    public function setInventarioEndereco($inventarioEndereco)
    {
        $this->inventarioEndereco = $inventarioEndereco;
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
}
