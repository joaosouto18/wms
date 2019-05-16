<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 18/12/2018
 * Time: 09:53
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Wms\Domain\Configurator;
use Wms\Domain\Entity\Inventario;
use Wms\Domain\Entity\Usuario;

/**
 * @Table(name="INVENTARIO_ANDAMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\InventarioNovo\InventarioAndamentoRepository")
 */
class InventarioAndamento
{
    const STATUS_GERADO       = 0;
    const STATUS_LIBERADO     = 1;
    const STATUS_CONCLUIDO    = 2;
    const STATUS_FINALIZADO   = 3;
    const STATUS_INTERROMPIDO = 4;
    const STATUS_CANCELADO    = 5;
    const REMOVER_ENDERECO    = 6;
    const REMOVER_PRODUTO     = 7;

    public static $tipoStatus = array(
        self::STATUS_GERADO => "GERADO",
        self::STATUS_LIBERADO => "LIBERADO",
        self::STATUS_CONCLUIDO => "CONCLUIDO",
        self::STATUS_FINALIZADO => "FINALIZADO",
        self::STATUS_INTERROMPIDO => "INTERROMPIDO",
        self::STATUS_CANCELADO => "CANCELADO",
        self::REMOVER_ENDERECO => "ENDEREÇO REMOVIDO",
        self::REMOVER_PRODUTO => "PRODUTO REMOVIDO"
    );

    /**
     * @Column(name="COD_INVENTARIO_ANDAMENTO", type="integer", length=8, nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_INVENTARIO_ANDAMENTO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var Inventario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\InventarioNovo")
     * @JoinColumn(name="COD_INVENTARIO", referencedColumnName="COD_INVENTARIO")
     */
    protected $inventario;

    /**
     * @var Usuario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * @var string $descricao
     * @Column(name="DESCRICAO", type="string")
     */
    protected $descricao;

    /**
     * @var \DateTime $dthAcao
     * @Column(name="DTH_ACAO", type="datetime", nullable=false)
     */
    protected $dthAcao;

    /**
     * @var \DateTime $codAcao
     * @Column(name="COD_ACAO", type="integer", nullable=false)
     */
    protected $codAcao;

    public function __construct()
    {
        self::setDthAcao();
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
    public function getDescricao()
    {
        return $this->descricao;
    }

    /**
     * @param mixed $descricao
     */
    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
    }

    /**
     * @param $toString boolean Converter para String a data
     * @return \DateTime|string
     */
    public function getDthAcao($toString = false)
    {
        return ($toString && !empty($this->dthAcao)) ? $this->dthAcao->format('d/m/Y H:i:s') : $this->dthAcao ;
    }

    private function setDthAcao()
    {
        $this->dthAcao = new \DateTime();
    }

    /**
     * @return Inventario
     */
    public function getInventario()
    {
        return $this->inventario;
    }

    /**
     * @param Inventario $inventario
     */
    public function setInventario($inventario)
    {
        $this->inventario = $inventario;
    }

    /**
     * @return Usuario
     */
    public function getUsuario()
    {
        return $this->usuario;
    }

    /**
     * @param Usuario $usuario
     */
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
    }

    /**
     * @return \DateTime
     */
    public function getCodAcao()
    {
        return $this->codAcao;
    }

    /**
     * @param \DateTime $codAcao
     */
    public function setCodAcao($codAcao)
    {
        $this->codAcao = $codAcao;
    }


    public function toArray()
    {
        return Configurator::configureToArray($this);
    }

}