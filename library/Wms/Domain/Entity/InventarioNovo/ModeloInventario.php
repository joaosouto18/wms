<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 23/11/2018
 * Time: 15:14
 */

namespace Wms\Domain\Entity\InventarioNovo;

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
     * @var string $ativo
     * @Column(name="IND_ATIVO", type="string", length=1, nullable=false)
     */
    protected $ativo;

    /**
     * @var \DateTime $ativo
     * @Column(name="DTH_CRIACAO", type="datetime", nullable=false)
     */
    protected $dthCriacao;

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
     * @var string $ativo
     * @Column(name="IND_IMPORTA_ERP", type="string", length=1, nullable=false)
     */
    protected $importaERP;

    /**
     * @var string $ativo
     * @Column(name="ID_MODELO", type="string", length=1, nullable=false)
     */
    protected $idLayoutEXP;

    /**
     * @var string $ativo
     * @Column(name="IND_DEFAULT", type="string", length=1, nullable=false)
     */
    protected $default;

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
     * @return \DateTime
     */
    public function getDthCriacao()
    {
        return $this->dthCriacao;
    }

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
        $this->itemAItem = ($itemAItem) ? 'S' : 'N';
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
        $this->controlaValidade = ($controlaValidade) ? 'S' : 'N';
    }

    /**
     * @return bool
     */
    public function controlaValidade()
    {
        return self::convertBoolean($this->controlaValidade);
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
        $this->exigeUMA = ($exigeUMA) ? 'S' : 'N';
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
        $this->comparaEstoque = ($comparaEstoque) ? 'S' : 'N';
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
        $this->usuarioNContagens = ($usuarioNContagens) ? 'S' : 'N';
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
        $this->contarTudo = ($contarTudo) ? 'S' : 'N';
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
        $this->volumesSeparadamente = ($volumesSeparadamente) ? 'S' : 'N';
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
    public function getImportaERP()
    {
        return $this->importaERP;
    }

    /**
     * @param boolean $importaERP
     */
    public function setImportaERP($importaERP)
    {
        $this->importaERP = ($importaERP) ? 'S' : 'N';
    }

    /**
     * @return bool
     */
    public function importaERP()
    {
        return self::convertBoolean($this->importaERP);
    }

    /**
     * @return string
     */
    public function getIdLayoutEXP()
    {
        return $this->idLayoutEXP;
    }

    /**
     * @param integer $idLayoutEXP
     */
    public function setIdLayoutEXP($idLayoutEXP)
    {
        $this->idLayoutEXP = $idLayoutEXP;
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
        $this->default = ($default) ? 'S' : 'N';
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
}