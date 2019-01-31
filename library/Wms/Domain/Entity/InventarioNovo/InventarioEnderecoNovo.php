<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 23/11/2018
 * Time: 15:02
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Domain\Entity\InventarioNovo;
use Wms\Domain\Configurator;

/**
 * @Table(name="INVENTARIO_ENDERECO_NOVO")
 * @Entity(repositoryClass="Wms\Domain\Entity\InventarioNovo\InventarioEnderecoNovoRepository")
 */
class InventarioEnderecoNovo
{
    const STATUS_PENDENTE    = 0;
    const STATUS_CONFERENCIA = 1;
    const STATUS_DIVERGENCIA = 2;
    const STATUS_FINALIZADO  = 3;

    public static $tipoStatus = array(
        self::STATUS_PENDENTE => "PENDENTE",
        self::STATUS_CONFERENCIA => "LIBERADO",
        self::STATUS_DIVERGENCIA => "CONCLUIDO",
        self::STATUS_FINALIZADO => "FINALIZADO"
    );

    /**
     * @Column(name="COD_INVENTARIO_ENDERECO", type="integer", length=8, nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_N_INV_END_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var InventarioNovo $codInventario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\InventarioNovo")
     * @JoinColumn(name="COD_INVENTARIO", referencedColumnName="COD_INVENTARIO")
     */
    protected $inventario;

    /**
     * @var Endereco $depositoEndereco
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $depositoEndereco;

    /**
     * @Column(name="NUM_CONTAGEM", type="integer", length=3 )
     */
    protected $contagem;

    /**
     * @var int
     * @Column(name="COD_STATUS", type="integer" )
     */
    protected $status;

    /**
     * @Column(name="IND_ATIVO", type="string" )
     */
    protected $ativo;

    public function __construct()
    {
        $this->setPendente(self::STATUS_PENDENTE);
    }

    /**
     * @param mixed $status
     */
    private function setStatus($status)
    {
        $this->status = $status;
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
     * @return InventarioNovo
     */
    public function getInventario()
    {
        return $this->inventario;
    }

    /**
     * @param InventarioNovo $inventario
     */
    public function setInventario($inventario)
    {
        $this->inventario = $inventario;
    }

    /**
     * @return Endereco
     */
    public function getDepositoEndereco()
    {
        return $this->depositoEndereco;
    }

    /**
     * @param Endereco $depositoEndereco
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
    public function getStatus()
    {
        return $this->status;
    }

    public function setPendente()
    {
        $this->setStatus(self::STATUS_PENDENTE);
    }

    public function setConferencia()
    {
        $this->setStatus(self::STATUS_CONFERENCIA);
    }

    public function setDivergencia()
    {
        $this->setStatus(self::STATUS_DIVERGENCIA);
    }

    public function setFinalizado()
    {
        $this->setStatus(self::STATUS_FINALIZADO);
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
        return self::convertBoolean($this->ativo);
    }

    public function toArray()
    {
        return Configurator::configureToArray($this);
    }

    private function convertBoolean($param)
    {
        return ($param === 'S');
    }
}