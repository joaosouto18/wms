<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 23/11/2018
 * Time: 14:49
 */

namespace Wms\Domain\Entity;

/**
 * @Table(name="INVENTARIO_NOVO")
 * @Entity(repositoryClass="Wms\Domain\Entity\InventarioNovoRepository")
 */
class InventarioNovo
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
     * @Column(name="COD_INVENTARIO", type="integer", length=8, nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_INVENTARIO_NOVO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DSC_INVENTARIO", type="string")
     */
    protected $descricao;

    /**
     * @Column(name="DTH_INICIO", type="datetime")
     */
    protected $inicio;

    /**
     * @Column(name="DTH_FINALIZACAO", type="datetime")
     */
    protected $finalizacao;

    /**
     * @Column(name="COD_STATUS", type="integer" )
     */
    protected $status;

    /**
     * @Column(name="COD_INVENTARIO_ERP", type="integer", length=8 )
     */
    protected $codErp;


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
     * @return mixed
     */
    public function getInicio()
    {
        return $this->inicio;
    }

    /**
     * @param mixed $inicio
     */
    public function setInicio($inicio)
    {
        $this->inicio = $inicio;
    }

    /**
     * @return mixed
     */
    public function getFinalizacao()
    {
        return $this->finalizacao;
    }

    /**
     * @param mixed $finalizacao
     */
    public function setFinalizacao($finalizacao)
    {
        $this->finalizacao = $finalizacao;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getCodErp()
    {
        return $this->codErp;
    }

    /**
     * @param mixed $codErp
     */
    public function setCodErp($codErp)
    {
        $this->codErp = $codErp;
    }

}
