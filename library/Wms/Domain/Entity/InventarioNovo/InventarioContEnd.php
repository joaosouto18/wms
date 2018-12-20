<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 23/11/2018
 * Time: 15:23
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Wms\Domain\Configurator;

/**
 * @Table(name="INVENTARIO_CONT_END")
 * @Entity(repositoryClass="Wms\Domain\Entity\InventarioNovo\InventarioContEndRepository")
 */
class InventarioContEnd
{
    /**
     * @var int
     * @Column(name="COD_INV_CONT_END", type="integer", length=8, nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_N_INV_CONT_END_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var InventarioEnderecoNovo $inventarioEndereco
     * @ManyToOne(targetEntity="Wms\Domain\Entity\InventarioNovo\InventarioEnderecoNovo")
     * @JoinColumn(name="COD_INVENTARIO_ENDERECO", referencedColumnName="COD_INVENTARIO_ENDERECO")
     */
    protected $inventarioEndereco;

    /**
     * @var int
     * @Column(name="NUM_SEQUENCIA", type="integer", length=8)
     */
    protected $sequencia;

    /**
     * @var string
     * @Column(name="IND_CONTAGEM_DIVERGENCIA", type="string" )
     */
    protected $contagemDivergencia;

    /**
     * @var int
     * @Column(name="NUM_CONTAGEM", type="integer", length=3 )
     */
    protected $contagem;


    /**
     * @return int
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
     * @return int
     */
    public function getSequencia()
    {
        return $this->sequencia;
    }

    /**
     * @param int $sequencia
     */
    public function setSequencia($sequencia)
    {
        $this->sequencia = $sequencia;
    }

    /**
     * @return string
     */
    public function getContagemDivergencia()
    {
        return $this->contagemDivergencia;
    }

    /**
     * @param bool|string $contagemDivergencia
     */
    public function setContagemDivergencia($contagemDivergencia)
    {
        $this->contagemDivergencia = ((is_bool($contagemDivergencia) && $contagemDivergencia) ||
                                        (is_string($contagemDivergencia) && $contagemDivergencia == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return bool
     */
    public function isContagemDivergencia()
    {
        return self::convertBoolean($this->contagemDivergencia);
    }

    /**
     * @return int
     */
    public function getContagem()
    {
        return $this->contagem;
    }

    /**
     * @param int $contagem
     */
    public function setContagem($contagem)
    {
        $this->contagem = $contagem;
    }

    private function convertBoolean($param)
    {
        return ($param === 'S');
    }

    public function toArray()
    {
        return Configurator::configureToArray($this);
    }
}