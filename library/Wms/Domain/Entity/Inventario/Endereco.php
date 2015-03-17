<?php

namespace Wms\Domain\Entity\Inventario;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="INVENTARIO_ENDERECO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Inventario\EnderecoRepository")
 */
class Endereco
{

    /**
     * @Id
     * @Column(name="COD_INVENTARIO_ENDERECO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_INV_END_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $depositoEndereco;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Inventario")
     * @JoinColumn(name="COD_INVENTARIO", referencedColumnName="COD_INVENTARIO")
     */
    protected $inventario;

    /**
     * @Column(name="DIVERGENCIA")
     */
    protected $divergencia;

    /**
     * @Column(name="INVENTARIADO")
     */
    protected $inventariado;

    /**
     * @Column(name="ATUALIZA_ESTOQUE")
     */
    protected $atualizaEstoque;

    /**
     * @ORM\OneToMany(targetEntity="Wms\Domain\Entity\Inventario\ContagemEndereco", mappedBy="inventarioEndereco")
     */
    protected $contagemEndereco;

    public function __construct()
    {
        $this->contagemEndereco = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getDepositoEndereco()
    {
        return $this->depositoEndereco;
    }

    /**
     * @param mixed $depositoEndereco
     */
    public function setDepositoEndereco($depositoEndereco)
    {
        $this->depositoEndereco = $depositoEndereco;
    }

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
    public function getInventario()
    {
        return $this->inventario;
    }

    /**
     * @param mixed $inventario
     */
    public function setInventario($inventario)
    {
        $this->inventario = $inventario;
    }

    /**
     * @return mixed
     */
    public function getDivergencia()
    {
        return $this->divergencia;
    }

    /**
     * @param mixed $divergencia
     */
    public function setDivergencia($divergencia)
    {
        $this->divergencia = $divergencia;
    }

    /**
     * @return mixed
     */
    public function getInventariado()
    {
        return $this->inventariado;
    }

    /**
     * @param mixed $inventariado
     */
    public function setInventariado($inventariado)
    {
        $this->inventariado = $inventariado;
    }

    /**
     * @return mixed
     */
    public function getContagemEndereco()
    {
        return $this->contagemEndereco;
    }

    /**
     * @param mixed $contagemEndereco
     */
    public function setContagemEndereco($contagemEndereco)
    {
        $this->contagemEndereco = $contagemEndereco;
    }

    /**
     * @return mixed
     */
    public function getAtualizaEstoque()
    {
        return $this->atualizaEstoque;
    }

    /**
     * @param mixed $atualizaEstoque
     */
    public function setAtualizaEstoque($atualizaEstoque)
    {
        $this->atualizaEstoque = $atualizaEstoque;
    }

}
