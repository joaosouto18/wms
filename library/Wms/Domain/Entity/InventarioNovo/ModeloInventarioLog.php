<?php
/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 13/12/2018
 * Time: 11:52
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Wms\Domain\Entity\Usuario;

/**
 * @Table(name="MODELO_INVENTARIO_LOG")
 * @Entity(repositoryClass="Wms\Domain\Entity\InventarioNovo\ModeloInventarioLogRepository")
 */
class ModeloInventarioLog
{
    /**
     * @Column(name="COD_LOG", type="integer", length=8, nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_MODELO_INVENTARIO_LOG_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var \DateTime $dthRegistro
     * @Column(name="DTH_REGISTRO", type="datetime", nullable=false)
     */
    protected $dthRegistro;

    /**
     * @var ModeloInventario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\InventarioNovo\ModeloInventario")
     * @JoinColumn(name="COD_MODELO_INVENTARIO", referencedColumnName="COD_MODELO_INVENTARIO")
     */
    protected $modeloInventario;

    /**
     * @var Usuario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * @var string $descricao
     * @Column(name="DSC_MODELO_DE", type="string")
     */
    protected $descricaoAntes;

    /**
     * @var string $descricao
     * @Column(name="DSC_MODELO_PARA", type="string")
     */
    protected $descricaoDepois;

    /**
     * @var string $ativo
     * @Column(name="IND_ATIVO_DE", type="string", length=1, nullable=false)
     */
    protected $ativoAntes;

    /**
     * @var string $ativo
     * @Column(name="IND_ATIVO_PARA", type="string", length=1, nullable=false)
     */
    protected $ativoDepois;

    /**
     * @var string $ativo
     * @Column(name="IND_ITEM_A_ITEM_DE", type="string", length=1, nullable=false)
     */
    protected $itemAItemAntes;

    /**
     * @var string $ativo
     * @Column(name="IND_ITEM_A_ITEM_PARA", type="string", length=1, nullable=false)
     */
    protected $itemAItemDepois;

    /**
     * @var string $ativo
     * @Column(name="IND_CONTROLA_VALIDADE_DE", type="string", length=1, nullable=false)
     */
    protected $controlaValidadeAntes;

    /**
     * @var string $ativo
     * @Column(name="IND_CONTROLA_VALIDADE_PARA", type="string", length=1, nullable=false)
     */
    protected $controlaValidadeDepois;

    /**
     * @var string $ativo
     * @Column(name="IND_EXIGE_UMA_DE", type="string", length=1, nullable=false)
     */
    protected $exigeUMAAntes;

    /**
     * @var string $ativo
     * @Column(name="IND_EXIGE_UMA_PARA", type="string", length=1, nullable=false)
     */
    protected $exigeUMADepois;

    /**
     * @var integer $ativo
     * @Column(name="NUM_CONTAGENS_DE", type="integer", length=2, nullable=false)
     */
    protected $numContagensAntes;

    /**
     * @var integer $ativo
     * @Column(name="NUM_CONTAGENS_PARA", type="integer", length=2, nullable=false)
     */
    protected $numContagensDepois;

    /**
     * @var string $ativo
     * @Column(name="IND_COMPARA_ESTOQUE_DE", type="string", length=1, nullable=false)
     */
    protected $comparaEstoqueAntes;

    /**
     * @var string $ativo
     * @Column(name="IND_COMPARA_ESTOQUE_PARA", type="string", length=1, nullable=false)
     */
    protected $comparaEstoqueDepois;

    /**
     * @var string $ativo
     * @Column(name="IND_USUARIO_N_CONTAGENS_DE", type="string", length=1, nullable=false)
     */
    protected $usuarioNContagensAntes;

    /**
     * @var string $ativo
     * @Column(name="IND_USUARIO_N_CONTAGENS_PARA", type="string", length=1, nullable=false)
     */
    protected $usuarioNContagensDepois;

    /**
     * @var string $ativo
     * @Column(name="IND_CONTAR_TUDO_DE", type="string", length=1, nullable=false)
     */
    protected $contarTudoAntes;

    /**
     * @var string $ativo
     * @Column(name="IND_CONTAR_TUDO_PARA", type="string", length=1, nullable=false)
     */
    protected $contarTudoDepois;

    /**
     * @var string $ativo
     * @Column(name="IND_VOLUMES_SEPARADAMENTE_DE", type="string", length=1, nullable=false)
     */
    protected $volumesSeparadamenteAntes;

    /**
     * @var string $ativo
     * @Column(name="IND_VOLUMES_SEPARADAMENTE_PARA", type="string", length=1, nullable=false)
     */
    protected $volumesSeparadamenteDepois;

    /**
     * @var string $ativo
     * @Column(name="IND_DEFAULT_DE", type="string", length=1, nullable=false)
     */
    protected $defaultAntes;

    /**
     * @var string $ativo
     * @Column(name="IND_DEFAULT_PARA", type="string", length=1, nullable=false)
     */
    protected $defaultDepois;

    public function __construct()
    {
        $this->setDthRegistro();
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
     * @param $toString boolean Converter para String a data
     * @return \DateTime|string
     */
    public function getDthRegistro($toString = false)
    {
        return ($toString && !empty($this->dthRegistro)) ? $this->dthRegistro->format('d/m/Y H:i:s') : $this->dthRegistro ;
    }

    private function setDthRegistro()
    {
        $this->dthRegistro = new \DateTime();
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
    public function getDescricaoAntes()
    {
        return $this->descricaoAntes;
    }

    /**
     * @param string $descricaoAntes
     */
    public function setDescricaoAntes($descricaoAntes)
    {
        $this->descricaoAntes = $descricaoAntes;
    }

    /**
     * @return string
     */
    public function getDescricaoDepois()
    {
        return $this->descricaoDepois;
    }

    /**
     * @param string $descricaoDepois
     */
    public function setDescricaoDepois($descricaoDepois)
    {
        $this->descricaoDepois = $descricaoDepois;
    }

    /**
     * @return string
     */
    public function getAtivoAntes()
    {
        return $this->ativoAntes;
    }

    /**
     * @param string $ativoAntes
     */
    public function setAtivoAntes($ativoAntes)
    {
        $this->ativoAntes = ((is_bool($ativoAntes) && $ativoAntes) || (is_string($ativoAntes) && $ativoAntes == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getAtivoDepois()
    {
        return $this->ativoDepois;
    }

    /**
     * @param string $ativoDepois
     */
    public function setAtivoDepois($ativoDepois)
    {
        $this->ativoDepois = ((is_bool($ativoDepois) && $ativoDepois) || (is_string($ativoDepois) && $ativoDepois == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getItemAItemAntes()
    {
        return $this->itemAItemAntes;
    }

    /**
     * @param string $itemAItemAntes
     */
    public function setItemAItemAntes($itemAItemAntes)
    {
        $this->itemAItemAntes = ((is_bool($itemAItemDepois) && $itemAItemDepois) || (is_string($itemAItemDepois) && $itemAItemDepois == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getItemAItemDepois()
    {
        return $this->itemAItemDepois;
    }

    /**
     * @param string $itemAItemDepois
     */
    public function setItemAItemDepois($itemAItemDepois)
    {
        $this->itemAItemDepois = ((is_bool($itemAItemDepois) && $itemAItemDepois) || (is_string($itemAItemDepois) && $itemAItemDepois == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getControlaValidadeAntes()
    {
        return $this->controlaValidadeAntes;
    }

    /**
     * @param string $controlaValidadeAntes
     */
    public function setControlaValidadeAntes($controlaValidadeAntes)
    {
        $this->controlaValidadeAntes = $controlaValidadeAntes;
    }

    /**
     * @return string
     */
    public function getControlaValidadeDepois()
    {
        return $this->controlaValidadeDepois;
    }

    /**
     * @param string $controlaValidadeDepois
     */
    public function setControlaValidadeDepois($controlaValidadeDepois)
    {
        $this->controlaValidadeDepois = $controlaValidadeDepois;
    }

    /**
     * @return string
     */
    public function getExigeUMAAntes()
    {
        return $this->exigeUMAAntes;
    }

    /**
     * @param string $exigeUMAAntes
     */
    public function setExigeUMAAntes($exigeUMAAntes)
    {
        $this->exigeUMAAntes = ((is_bool($exigeUMAAntes) && $exigeUMAAntes) || (is_string($exigeUMAAntes) && $exigeUMAAntes == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getExigeUMADepois()
    {
        return $this->exigeUMADepois;
    }

    /**
     * @param string $exigeUMADepois
     */
    public function setExigeUMADepois($exigeUMADepois)
    {
        $this->exigeUMADepois = ((is_bool($exigeUMADepois) && $exigeUMADepois) || (is_string($exigeUMADepois) && $exigeUMADepois == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return int
     */
    public function getNumContagensAntes()
    {
        return $this->numContagensAntes;
    }

    /**
     * @param int $numContagensAntes
     */
    public function setNumContagensAntes($numContagensAntes)
    {
        $this->numContagensAntes = $numContagensAntes;
    }

    /**
     * @return int
     */
    public function getNumContagensDepois()
    {
        return $this->numContagensDepois;
    }

    /**
     * @param int $numContagensDepois
     */
    public function setNumContagensDepois($numContagensDepois)
    {
        $this->numContagensDepois = $numContagensDepois;
    }

    /**
     * @return string
     */
    public function getComparaEstoqueAntes()
    {
        return $this->comparaEstoqueAntes;
    }

    /**
     * @param string $comparaEstoqueAntes
     */
    public function setComparaEstoqueAntes($comparaEstoqueAntes)
    {
        $this->comparaEstoqueAntes = ((is_bool($comparaEstoqueDepois) && $comparaEstoqueDepois) || (is_string($comparaEstoqueDepois) && $comparaEstoqueDepois == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getComparaEstoqueDepois()
    {
        return $this->comparaEstoqueDepois;
    }

    /**
     * @param string $comparaEstoqueDepois
     */
    public function setComparaEstoqueDepois($comparaEstoqueDepois)
    {
        $this->comparaEstoqueDepois = ((is_bool($comparaEstoqueDepois) && $comparaEstoqueDepois) || (is_string($comparaEstoqueDepois) && $comparaEstoqueDepois == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getUsuarioNContagensAntes()
    {
        return $this->usuarioNContagensAntes;
    }

    /**
     * @param string $usuarioNContagensAntes
     */
    public function setUsuarioNContagensAntes($usuarioNContagensAntes)
    {
        $this->usuarioNContagensAntes = ((is_bool($usuarioNContagensAntes) && $usuarioNContagensAntes) ||
                                         (is_string($usuarioNContagensAntes) && $usuarioNContagensAntes == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getUsuarioNContagensDepois()
    {
        return $this->usuarioNContagensDepois;
    }

    /**
     * @param string $usuarioNContagensDepois
     */
    public function setUsuarioNContagensDepois($usuarioNContagensDepois)
    {
        $this->usuarioNContagensDepois = ((is_bool($usuarioNContagensDepois) && $usuarioNContagensDepois) ||
                                          (is_string($usuarioNContagensDepois) && $usuarioNContagensDepois == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getContarTudoAntes()
    {
        return $this->contarTudoAntes;
    }

    /**
     * @param string $contarTudoAntes
     */
    public function setContarTudoAntes($contarTudoAntes)
    {
        $this->contarTudoAntes = ((is_bool($contarTudoAntes) && $contarTudoAntes) || (is_string($contarTudoAntes) && $contarTudoAntes == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getContarTudoDepois()
    {
        return $this->contarTudoDepois;
    }

    /**
     * @param string $contarTudoDepois
     */
    public function setContarTudoDepois($contarTudoDepois)
    {
        $this->contarTudoDepois = ((is_bool($contarTudoDepois) && $contarTudoDepois) || (is_string($contarTudoDepois) && $contarTudoDepois == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getVolumesSeparadamenteAntes()
    {
        return $this->volumesSeparadamenteAntes;
    }

    /**
     * @param string $volumesSeparadamenteAntes
     */
    public function setVolumesSeparadamenteAntes($volumesSeparadamenteAntes)
    {
        $this->volumesSeparadamenteAntes = ((is_bool($volumesSeparadamenteAntes) && $volumesSeparadamenteAntes) ||
                                            (is_string($volumesSeparadamenteAntes) && $volumesSeparadamenteAntes == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getVolumesSeparadamenteDepois()
    {
        return $this->volumesSeparadamenteDepois;
    }

    /**
     * @param string $volumesSeparadamenteDepois
     */
    public function setVolumesSeparadamenteDepois($volumesSeparadamenteDepois)
    {
        $this->volumesSeparadamenteDepois = ((is_bool($volumesSeparadamenteDepois) && $volumesSeparadamenteDepois) ||
                                             (is_string($volumesSeparadamenteDepois) && $volumesSeparadamenteDepois == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getDefaultAntes()
    {
        return $this->defaultAntes;
    }

    /**
     * @param string $defaultAntes
     */
    public function setDefaultAntes($defaultAntes)
    {
        $this->defaultAntes = ((is_bool($defaultAntes) && $defaultAntes) || (is_string($defaultAntes) && $defaultAntes == 'S') ) ? 'S' : 'N';
    }

    /**
     * @return string
     */
    public function getDefaultDepois()
    {
        return $this->defaultDepois;
    }

    /**
     * @param string $defaultDepois
     */
    public function setDefaultDepois($defaultDepois)
    {
        $this->defaultDepois = ((is_bool($defaultDepois) && $defaultDepois) || (is_string($defaultDepois) && $defaultDepois == 'S') ) ? 'S' : 'N';
    }

}