<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 23/11/2018
 * Time: 15:23
 */

namespace Wms\Domain\Entity\InventarioNovo;

/**
 * @Table(name="INVENTARIO_CONT_END")
 * @Entity(repositoryClass="Wms\Domain\Entity\InventarioNovo\InventarioContEndRepository")
 */
class InventarioContEnd
{
    /**
     * @Column(name="COD_INV_CONT_END", type="integer", length=8, nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_COD_INV_CONT_END_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\InventarioNovo\InventarioEnderecoNovo")
     * @JoinColumn(name="COD_INVENTARIO_ENDERECO", referencedColumnName="COD_INVENTARIO_ENDERECO")
     */
    protected $inventarioEndereco;

    /**
     * @Column(name="NUM_SEQUENCIA", type="integer" length=8)
     */
    protected $sequencia;

    /**
     * @Column(name="IND_CONTAGEM_DIVERGENCIA", type="char" )
     */
    protected $contagemDivergencia;

    /**
     * @Column(name="NUM_CONTAGEM", type="integer", length=3 )
     */
    protected $contagem;


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
    public function getSequencia()
    {
        return $this->sequencia;
    }

    /**
     * @param mixed $sequencia
     */
    public function setSequencia($sequencia)
    {
        $this->sequencia = $sequencia;
    }

    /**
     * @return mixed
     */
    public function getContagemDivergencia()
    {
        return $this->contagemDivergencia;
    }

    /**
     * @param mixed $contagemDivergencia
     */
    public function setContagemDivergencia($contagemDivergencia)
    {
        $this->contagemDivergencia = $contagemDivergencia;
    }

    /**
     * @return mixed
     */
    public function getContagem()
    {
        return $this->contagem;
    }

    /**
     * @param mixed $contagem
     */
    public function setContagem($contagem)
    {
        $this->contagem = $contagem;
    }
}