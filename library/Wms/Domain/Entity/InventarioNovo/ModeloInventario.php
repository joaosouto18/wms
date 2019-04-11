<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 23/11/2018
 * Time: 15:14
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Wms\Domain\Configurator;
use Wms\Domain\Entity\Usuario;

/**
 * @Table(name="MODELO_INVENTARIO")
 * @Entity(repositoryClass="Wms\Domain\Entity\InventarioNovo\ModeloInventarioRepository")
 */
class ModeloInventario
{

    const VALIDADE_NAO_CONTROLA = 'N';
    const VALIDADE_RECEBE = 'R';
    const VALIDADE_VALIDA = 'V';

    public static $statusValidade = [
        self::VALIDADE_NAO_CONTROLA => 'Não Controla',
        self::VALIDADE_RECEBE => 'Apenas confere',
        self::VALIDADE_VALIDA => 'Confere e Valida'
    ];

    /**
     * @Column(name="COD_MODELO_INVENTARIO", type="integer", length=8, nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_MODELO_INVENTARIO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var string $descricao
     * @Column(name="DSC_MODELO", type="string")
     */
    protected $descricao;

    /**
     * @var Usuario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * @var string $ativo
     * @Column(name="IND_ATIVO", type="string", length=1, nullable=false)
     */
    protected $ativo;

    /**
     * @var \DateTime $dthCriacao
     * @Column(name="DTH_CRIACAO", type="datetime", nullable=false)
     */
    protected $dthCriacao;

    /**
     * @var string $itemAItem
     * @Column(name="IND_ITEM_A_ITEM", type="string", length=1, nullable=false)
     */
    protected $itemAItem;

    /**
     * @var string $controlaValidade
     * @Column(name="IND_CONTROLA_VALIDADE", type="string", length=1, nullable=false)
     */
    protected $controlaValidade;

    /**
     * @var string $exigeUMA
     * @Column(name="IND_EXIGE_UMA", type="string", length=1, nullable=false)
     */
    protected $exigeUMA;

    /**
     * @var integer $numContagens
     * @Column(name="NUM_CONTAGENS", type="integer", length=2, nullable=false)
     */
    protected $numContagens;

    /**
     * @var string $comparaEstoque
     * @Column(name="IND_COMPARA_ESTOQUE", type="string", length=1, nullable=false)
     */
    protected $comparaEstoque;

    /**
     * @var string $usuarioNContagens
     * @Column(name="IND_USUARIO_N_CONTAGENS", type="string", length=1, nullable=false)
     */
    protected $usuarioNContagens;

    /**
     * @var string $contarTudo
     * @Column(name="IND_CONTAR_TUDO", type="string", length=1, nullable=false)
     */
    protected $contarTudo;

    /**
     * @var string $volumesSeparadamente
     * @Column(name="IND_VOLUMES_SEPARADAMENTE", type="string", length=1, nullable=false)
     */
    protected $volumesSeparadamente;

    /**
     * @var string $default
     * @Column(name="IND_DEFAULT", type="string", length=1, nullable=false)
     */
    protected $default;

    /**
     * ModeloInventario constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        self::setDthCriacao();
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
     * @return string
     */
    public function getDescricao()
    {
        return $this->descricao;
    }

    /**
     * @param string $descricao
     */
    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
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
     * @return string
     */
    public function getAtivo()
    {
        return $this->ativo;
    }

    /**
     * @param string $ativo
     */
    public function setAtivo($ativo)
    {
        $this->ativo = ($ativo) ? 'S' : 'N';
    }

    /**
     * @return bool
     */
    public function isAtivo()
    {
        return self::convertBoolean($this->ativo);
    }

    /**
     * @param $toString boolean Converter para String a data
     * @return \DateTime | string
     */
    public function getDthCriacao($toString = false)
    {
        return ($toString && !empty($this->dthCriacao)) ? $this->dthCriacao->format('d/m/Y H:i:s') : $this->dthCriacao ;
    }

    /**
     * @throws \Exception
     */
    private function setDthCriacao()
    {
        $this->dthCriacao = new \DateTime();
    }

    /**
     * @return string
     */
    public function getItemAItem()
    {
        return $this->itemAItem;
    }

    /**
     * @param string $itemAItem
     */
    public function setItemAItem($itemAItem)
    {
        $this->itemAItem = ((is_bool($itemAItem) && $itemAItem) || (is_string($itemAItem) && $itemAItem == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return bool
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
     * @return bool
     */
    public function controlaValidade()
    {
        return self::$statusValidade[$this->controlaValidade];
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
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
     */
    public function confereVolumesSeparadamente()
    {
        return self::convertBoolean($this->volumesSeparadamente);
    }

    /**
     * @return string
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param boolean $default
     */
    public function setDefault($default)
    {
        $this->default = ((is_bool($default) && $default) || (is_string($default) && $default == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return self::convertBoolean($this->default);
    }

    private function convertBoolean($param)
    {
        return ($param === 'S');
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function toArray()
    {
        return Configurator::configureToArray($this);
    }
}