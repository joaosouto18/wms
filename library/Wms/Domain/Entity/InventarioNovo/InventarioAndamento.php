<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 18/12/2018
 * Time: 09:53
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Wms\Domain\Configurator;

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

    public static $tipoStatus = array(
        self::STATUS_GERADO => "GERADO",
        self::STATUS_LIBERADO => "LIBERADO",
        self::STATUS_CONCLUIDO => "CONCLUIDO",
        self::STATUS_FINALIZADO => "FINALIZADO",
        self::STATUS_INTERROMPIDO => "INTERROMPIDO",
        self::STATUS_CANCELADO => "CANCELADO"
    );

    /**
     * @Column(name="COD_INVENTARIO_ANDAMENTO", type="integer", length=8, nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_INVENTARIO_ANDAMENTO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var codUsuario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\InventarioNovo")
     * @JoinColumn(name="COD_INVENTARIO", referencedColumnName="COD_INVENTARIO")
     */
    protected $codInventario;

    /**
     * @var codUsuario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $codUsuario;

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

    /**
     * @param \DateTime $dthAcao
     */
    public function setDthAcao($dthAcao)
    {
        $this->dthAcao = $dthAcao;
    }

    /**
     * @return mixed
     */
    public function geUsuario()
    {
        return $this->usuario;
    }

    /**
     * @param mixed $usuario
     */
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
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
    public function getAcao()
    {
        return $this->Acao;
    }

    /**
     * @param mixed $acao
     */
    public function setAcao($acao)
    {
        $this->acao = $acao;
    }

    public function toArray()
    {
        return Configurator::configureToArray($this);
    }

}