<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 23/11/2018
 * Time: 15:02
 */

namespace Wms\Domain\Entity\InventarioNovo;

/**
 * @Table(name="INVENTARIO_ENDERECO_NOVO")
 * @Entity(repositoryClass="Wms\Domain\Entity\InventarioNovo\InventarioEnderecoNovoRepository")
 */
class InventarioEnderecoNovo
{
    /**
     * @Column(name="COD_INVENTARIO_ENDERECO", type="integer", length=8, nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_INVENTARIO_ENDERECO_NOVO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var Wms\Domain\Entity\InventarioNovo $codInventario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\InventarioNovo\InventarioNovo")
     * @JoinColumn(name="COD_INVENTARIO", referencedColumnName="COD_INVENTARIO")
     */
    protected $codInventario;

    /**
     * @var Wms\Domain\Entity\Deposito\Endereco $depositoEndereco
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $depositoEndereco;

    /**
     * @Column(name="NUM_CONTAGEM", type="integer", length=3 )
     */
    protected $contagem;

    /**
     * @Column(name="IND_FINALIZADO", type="string" )
     */
    protected $finalizado;



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
    public function getCodInventario()
    {
        return $this->codInventario;
    }

    /**
     * @param mixed $codInventario
     */
    public function setCodInventario($codInventario)
    {
        $this->codInventario = $codInventario;
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

    /**
     * @return mixed
     */
    public function getFinalizado()
    {
        return $this->finalizado;
    }

    /**
     * @param mixed $finalizado
     */
    public function setFinalizado($finalizado)
    {
        $this->finalizado = $finalizado;
    }


}