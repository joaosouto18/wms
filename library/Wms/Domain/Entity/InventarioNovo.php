<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 23/11/2018
 * Time: 14:49
 */

namespace Wms\Domain\Entity;

use Wms\Domain\Configurator;
use Wms\Domain\Entity\InventarioNovo\ModeloInventario;

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

    const CRITERIO_PRODUTO = 'P';
    const CRITERIO_ENDERECO = 'E';

    /**
     * @Column(name="COD_INVENTARIO", type="integer", length=8, nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_N_INV_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DSC_INVENTARIO", type="string")
     */
    protected $descricao;

    /**
     * @var \DateTime $dthCriacao
     * @Column(name="DTH_CRIACAO", type="datetime")
     */
    protected $dthCriacao;

    /**
     * @var \DateTime $dthIicio
     * @Column(name="DTH_INICIO", type="datetime")
     */
    protected $dthInicio;

    /**
     * @var \DateTime $finalizacao
     * @Column(name="DTH_FINALIZACAO", type="datetime")
     */
    protected $dthFinalizacao;

    /**
     * @var int
     * @Column(name="COD_STATUS", type="integer" )
     */
    protected $status;

    /**
     * @var int
     * @Column(name="COD_INVENTARIO_ERP", type="integer", length=8 )
     */
    protected $codErp;

    /**
     * @var ModeloInventario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\InventarioNovo\ModeloInventario")
     * @JoinColumn(name="COD_MODELO_INVENTARIO", referencedColumnName="COD_MODELO_INVENTARIO")
     */
    protected $modeloInventario;

    /**
     * @var string $ativo
     * @Column(name="IND_ITEM_A_ITEM", type="string", length=1, nullable=false)
     */
    protected $itemAItem;

    /**
     * @var string $ativo
     * @Column(name="IND_CONTROLA_VALIDADE", type="string", length=1, nullable=false)
     */
    protected $controlaValidade;

    /**
     * @var string $ativo
     * @Column(name="IND_EXIGE_UMA", type="string", length=1, nullable=false)
     */
    protected $exigeUMA;

    /**
     * @var integer $ativo
     * @Column(name="NUM_CONTAGENS", type="integer", length=2, nullable=false)
     */
    protected $numContagens;

    /**
     * @var string $ativo
     * @Column(name="IND_COMPARA_ESTOQUE", type="string", length=1, nullable=false)
     */
    protected $comparaEstoque;

    /**
     * @var string $ativo
     * @Column(name="IND_USUARIO_N_CONTAGENS", type="string", length=1, nullable=false)
     */
    protected $usuarioNContagens;

    /**
     * @var string $ativo
     * @Column(name="IND_CONTAR_TUDO", type="string", length=1, nullable=false)
     */
    protected $contarTudo;

    /**
     * @var string $ativo
     * @Column(name="IND_VOLUMES_SEPARADAMENTE", type="string", length=1, nullable=false)
     */
    protected $volumesSeparadamente;

    /**
     * @var string
     * @Column(name="IND_CRITERIO", type="string", length=1, nullable=false)
     */
    protected $criterio;

    public function __construct()
    {
        $this->setStatus(self::STATUS_GERADO);
        $this->setDthCriacao();
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
    public function getDthCriacao($toString = false)
    {
        return ($toString && !empty($this->dthCriacao)) ? $this->dthCriacao->format('d/m/Y H:i:s') : $this->dthCriacao ;
    }

    private function setDthCriacao()
    {
        $this->dthCriacao = new \DateTime();
    }

    /**
     * @param $toString boolean Converter para String a data
     * @return \DateTime|string
     */
    public function getDthInicio($toString = false)
    {
        return ($toString && !empty($this->dthInicio)) ? $this->dthInicio->format('d/m/Y H:i:s') : $this->dthInicio ;
    }

    private function setDthInicio()
    {
        $this->dthInicio = new \DateTime();
    }

    /**
     * @param $toString boolean Converter para String a data
     * @return \DateTime|string
     */
    public function getDthFinalizacao($toString = false)
    {
        return ($toString && !empty($this->dthFinalizacao)) ? $this->dthFinalizacao->format('d/m/Y H:i:s') : $this->dthFinalizacao ;
    }

    private function setDthFinalizacao()
    {
        $this->dthFinalizacao = new \DateTime();
    }

    /**
     * @return ing
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function isGerado()
    {
        return ($this->status == self::STATUS_GERADO);
    }

    public function isLiberado()
    {
        return ($this->status == self::STATUS_LIBERADO);
    }

    public function isConcluido()
    {
        return ($this->status == self::STATUS_CONCLUIDO);
    }

    public function isFinalizado()
    {
        return ($this->status == self::STATUS_FINALIZADO);
    }

    public function isInterrompido()
    {
        return ($this->status == self::STATUS_INTERROMPIDO);
    }

    public function isCancelado()
    {
        return ($this->status == self::STATUS_CANCELADO);
    }

    /**
     * @param mixed $status
     */
    private function setStatus($status)
    {
        $this->status = $status;
    }

    public function liberar()
    {
        $this->setStatus(self::STATUS_LIBERADO);
        $this->setDthInicio();
    }

    public function concluir()
    {
        $this->setStatus(self::STATUS_CONCLUIDO);
    }

    public function finalizar()
    {
        $this->setStatus(self::STATUS_FINALIZADO);
        $this->setDthFinalizacao();
    }

    public function interromper()
    {
        $this->setStatus(self::STATUS_INTERROMPIDO);
    }

    public function cancelar()
    {
        $this->setStatus(self::STATUS_CANCELADO);
    }

    /**
     * @return string
     */
    public function getDscStatus()
    {
        return self::$tipoStatus[$this->status];
    }

    /**
     * @return int
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

    /**
     * @return ModeloInventario
     */
    public function getModeloInventario()
    {
        return $this->modeloInventario;
    }

    /**
     * @param ModeloInventario $modeloInventario
     */
    public function setModeloInventario($modeloInventario)
    {
        $this->modeloInventario = $modeloInventario;
    }

    /**
     * @return string
     */
    public function getItemAItem()
    {
        return $this->itemAItem;
    }

    /**
     * @param boolean $itemAItem
     */
    public function setItemAItem($itemAItem)
    {
        $this->itemAItem = ((is_bool($itemAItem) && $itemAItem) || (is_string($itemAItem) && $itemAItem == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return boolean
     */
    public function confereItemAItem()
    {
        return self::convertBoolean($this->itemAItem);
    }

    /**
     * @return string
     */
    public function getControlaValidade()
    {
        return $this->controlaValidade;
    }

    /**
     * @param boolean $controlaValidade
     */
    public function setControlaValidade($controlaValidade)
    {
        $this->controlaValidade = $controlaValidade;
    }

    /**
     * @return boolean
     */
    public function controlaValidade()
    {
        return ModeloInventario::$statusValidade[$this->controlaValidade];
    }

    /**
     * @return string
     */
    public function getExigeUMA()
    {
        return $this->exigeUMA;
    }

    /**
     * @param boolean $exigeUMA
     */
    public function setExigeUMA($exigeUMA)
    {
        $this->exigeUMA = ((is_bool($exigeUMA) && $exigeUMA) || (is_string($exigeUMA) && $exigeUMA == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return boolean
     */
    public function exigeUma()
    {
        return self::convertBoolean($this->exigeUMA);
    }

    /**
     * @return int
     */
    public function getNumContagens()
    {
        return $this->numContagens;
    }

    /**
     * @param int $numContagens
     */
    public function setNumContagens($numContagens)
    {
        $this->numContagens = $numContagens;
    }

    /**
     * @return string
     */
    public function getComparaEstoque()
    {
        return $this->comparaEstoque;
    }

    /**
     * @param boolean $comparaEstoque
     */
    public function setComparaEstoque($comparaEstoque)
    {
        $this->comparaEstoque = ((is_bool($comparaEstoque) && $comparaEstoque) ||
                                 (is_string($comparaEstoque) && $comparaEstoque == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return bool
     */
    public function comparaEstoque()
    {
        return self::convertBoolean($this->comparaEstoque);
    }

    /**
     * @return string
     */
    public function getUsuarioNContagens()
    {
        return $this->usuarioNContagens;
    }

    /**
     * @param boolean $usuarioNContagens
     */
    public function setUsuarioNContagens($usuarioNContagens)
    {
        $this->usuarioNContagens = ((is_bool($usuarioNContagens) && $usuarioNContagens) ||
                                    (is_string($usuarioNContagens) && $usuarioNContagens == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return boolean
     */
    public function permiteUsuarioNContagens()
    {
        return self::convertBoolean($this->usuarioNContagens);
    }

    /**
     * @return string
     */
    public function getContarTudo()
    {
        return $this->contarTudo;
    }

    /**
     * @param boolean $contarTudo
     */
    public function setContarTudo($contarTudo)
    {
        $this->contarTudo = ((is_bool($contarTudo) && $contarTudo) ||
                             (is_string($contarTudo) && $contarTudo == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return boolean
     */
    public function forcarContarTudo()
    {
        return self::convertBoolean($this->contarTudo);
    }

    /**
     * @return string
     */
    public function getVolumesSeparadamente()
    {
        return $this->volumesSeparadamente;
    }

    /**
     * @param boolean $volumesSeparadamente
     */
    public function setVolumesSeparadamente($volumesSeparadamente)
    {
        $this->volumesSeparadamente = ((is_bool($volumesSeparadamente) && $volumesSeparadamente) ||
                                       (is_string($volumesSeparadamente) && $volumesSeparadamente == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getCriterio()
    {
        return $this->criterio;
    }

    /**
     * @param string $criterio
     */
    public function setCriterio($criterio)
    {
        $this->criterio = $criterio;
    }

    /**
     * @return bool
     */
    public function isPorProduto()
    {
        return ($this->criterio === self::CRITERIO_PRODUTO);
    }

    /**
     * @return bool
     */
    public function isPorEndereco()
    {
        return ($this->criterio === self::CRITERIO_ENDERECO);
    }

    /**
     * @return boolean
     */
    public function confereVolumesSeparadamente()
    {
        return self::convertBoolean($this->volumesSeparadamente);
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
