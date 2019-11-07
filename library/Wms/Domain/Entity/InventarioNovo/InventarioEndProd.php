<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 23/11/2018
 * Time: 15:14
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Wms\Domain\Configurator;
use Wms\Domain\Entity\Produto;

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
     * @SequenceGenerator(sequenceName="SQ_N_INV_END_PROD_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var InventarioEnderecoNovo $inventarioEndereco
     * @ManyToOne(targetEntity="Wms\Domain\Entity\InventarioNovo\InventarioEnderecoNovo")
     * @JoinColumn(name="COD_INVENTARIO_ENDERECO", referencedColumnName="COD_INVENTARIO_ENDERECO")
     */
    protected $inventarioEndereco;

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
     * @var string
     * @Column(name="COD_PRODUTO", type="string")
     */
    protected $codProduto;

    /**
     * @var string
     * @Column(name="DSC_GRADE", type="string")
     */
    protected $grade;

    /**
     * @Column(name="IND_ATIVO", type="string" )
     */
    protected $ativo;

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
     * @return InventarioEnderecoNovo
     */
    public function getInventarioEndereco()
    {
        return $this->inventarioEndereco;
    }

    /**
     * @param InventarioEnderecoNovo $inventarioEndereco
     */
    public function setInventarioEndereco($inventarioEndereco)
    {
        $this->inventarioEndereco = $inventarioEndereco;
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
     * @return string
     */
    public function getAtivo()
    {
        return $this->ativo;
    }

    /**
     * @param boolean $ativo
     */
    public function setAtivo($ativo)
    {
        $this->ativo = ((is_bool($ativo) && $ativo) || (is_string($ativo) && $ativo == 'S') ) ? 'S' : 'N';
    }

    public function isAtivo()
    {
        return ($this->ativo === 'S');
    }

    public function toArray()
    {
        return Configurator::configureToArray($this);
    }
}