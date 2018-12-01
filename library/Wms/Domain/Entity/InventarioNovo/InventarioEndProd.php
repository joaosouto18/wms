<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 23/11/2018
 * Time: 15:14
 */

namespace Wms\Domain\Entity\InventarioNovo;

/**
 * @Table(name="INVENTARIO_END_PROD")
 * @Entity(repositoryClass="Wms\Domain\Entity\InventarioNovo\InventarioEndProdRepository")
 */
class InventarioEndProd
{
    /**
     * @Column(name="COD_INV_END_PROD", type="integer", length=8, nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_COD_INV_END_PROD_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var Wms\Domain\Entity\InventarioNovo $inventarioEndereco
     * @ManyToOne(targetEntity="Wms\Domain\Entity\InventarioNovo\InventarioEnderecoNovo")
     * @JoinColumn(name="COD_INVENTARIO_ENDERECO", referencedColumnName="COD_INVENTARIO_ENDERECO")
     */
    protected $inventarioEndereco;

    /**
     * @var Wms\Domain\Entity\Produto $produto
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO")
     */
    protected $produto;

    /**
     * @var Wms\Domain\Entity\Produto $grade
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     */
    protected $grade;


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
    public function getInventarioEndereco()
    {
        return $this->inventarioEndereco;
    }

    /**
     * @param mixed $inventarioEndereco
     */
    public function setInventarioEndereco($inventarioEndereco)
    {
        $this->inventarioEndereco = $inventarioEndereco;
    }

    /**
     * @return mixed
     */
    public function getProduto()
    {
        return $this->produto;
    }

    /**
     * @param mixed $produto
     */
    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    /**
     * @return mixed
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * @param mixed $grade
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
    }


}