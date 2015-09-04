<?php

namespace Wms\Domain\Entity\Deposito;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Endereco
 *
 * @Table(name="DEPOSITO_ENDERECO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Deposito\EnderecoRepository")
 */
class Endereco
{

    /**
     * @var integer $id
     *
     * @Column(name="COD_DEPOSITO_ENDERECO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_DEPOSITO_ENDERECO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var string $rua
     * @Column(name="NUM_RUA", type="integer", nullable=false)
     */
    protected $rua;

    /**
     * @Column(name="NUM_PREDIO", type="integer", nullable=false)
     */
    protected $predio;

    /**
     * @Column(name="NUM_NIVEL", type="integer", nullable=false)
     */
    protected $nivel;

    /**
     * @Column(name="NUM_APARTAMENTO", type="integer", nullable=false)
     */
    protected $apartamento;

    /**
     * @Column(name="IND_SITUACAO", type="string", length=1, nullable=false)
     */
    protected $situacao;

    /**
     * @Column(name="IND_STATUS", type="string", length=1, nullable=false)
     */
    protected $status;

    /**
     * @var smallint $idCaracteristica
     *
     * @Column(name="COD_CARACTERISTICA_ENDERECO", type="smallint", nullable=false)
     */
    protected $idCaracteristica;

    /**
     * @var Wms\Domain\Entity\Deposito\Endereco\Caracteristica $caracteristica
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco\Caracteristica")
     * @JoinColumn(name="COD_CARACTERISTICA_ENDERECO", referencedColumnName="COD_CARACTERISTICA_ENDERECO") 
     */
    protected $caracteristica;

    /**
     * @var smallint $idEstruturaArmazenagem
     *
     * @Column(name="COD_TIPO_EST_ARMAZ", type="smallint", nullable=false)
     */
    protected $idEstruturaArmazenagem;

    /**
     * @var Wms\Domain\Entity\Armazenagem\Estrutura\Tipo $estruturaArmazenagem
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Armazenagem\Estrutura\Tipo")
     * @JoinColumn(name="COD_TIPO_EST_ARMAZ", referencedColumnName="COD_TIPO_EST_ARMAZ") 
     */
    protected $estruturaArmazenagem;

    /**
     * @var smallint $idTipoEndereco
     *
     * @Column(name="COD_TIPO_ENDERECO", type="smallint", nullable=false)
     */
    protected $idTipoEndereco;

    /**
     * @var Wms\Domain\Entity\Deposito\Endereco\Tipo $tipoEndereco
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco\Tipo")
     * @JoinColumn(name="COD_TIPO_ENDERECO", referencedColumnName="COD_TIPO_ENDERECO") 
     */
    protected $tipoEndereco;

    /**
     * @var smallint $idAreaArmazenagem
     *
     * @Column(name="COD_AREA_ARMAZENAGEM", type="smallint", nullable=false)
     */
    protected $idAreaArmazenagem;

    /**
     * @var Wms\Domain\Entity\Deposito\AreaArmazenagem $areaArmazenagem
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\AreaArmazenagem")
     * @JoinColumn(name="COD_AREA_ARMAZENAGEM", referencedColumnName="COD_AREA_ARMAZENAGEM") 
     */
    protected $areaArmazenagem;

    /**
     * @var smallint $idDeposito
     * @Column(name="COD_DEPOSITO", type="smallint", nullable=false)
     */
    protected $idDeposito;

    /**
     * @var Wms\Domain\Entity\Deposito $deposito
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito")
     * @JoinColumn(name="COD_DEPOSITO", referencedColumnName="COD_DEPOSITO") 
     */
    protected $deposito;

    /**
     * @var string $descricao
     * @Column(name="DSC_DEPOSITO_ENDERECO", type="string", length=30, nullable=false)
     */
    protected $descricao;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Produto\Embalagem", mappedBy="endereco", cascade={"persist"})
     * @var ArrayCollection embalagens que compoem este endereco
     */
    protected $embalagens;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Produto\Volume", mappedBy="endereco", cascade={"persist"})
     * @var ArrayCollection volumes que compoem este endereco
     */
    protected $volumes;

    /**
     * @var string $ind_disponivel
     * @Column(name="IND_DISPONIVEL", type="string", length=30, nullable=false)
     */
    protected $disponivel;

    /**
     * @var string $ind_ativo
     * @Column(name="IND_ATIVO", type="string", length=30, nullable=false)
     */
    protected $ativo;

    /**
     * @Column(name="IND_INVENTARIO_BLOQUEADO", type="string")
     */
    protected $inventarioBloqueado;

    /**
     * @Column(name="DTH_VALIDADE", type="date")
     * @var date
     */
    protected $validade;

    /**
     * @param string $ativo
     */
    public function setAtivo($ativo)
    {
        $this->ativo = $ativo;
    }

    /**
     * @return string
     */
    public function getAtivo()
    {
        return $this->ativo;
    }



    /**
     * @param string $disponivel
     */
    public function setDisponivel($disponivel)
    {
        $this->disponivel = $disponivel;
    }

    /**
     * @return string
     */
    public function getDisponivel()
    {
        return $this->disponivel;
    }

    public function __construct()
    {
        $this->embalagens = new ArrayCollection;
        $this->volumes = new ArrayCollection;
    }
    
    /**
     * lista de tipos da situacao
     * @var array
     */
    public static $listaTipoLado = array(
        'T' => 'Todos',
        'I' => 'Impar',
        'P' => 'Par',
    );

    public function getId()
    {
        return $this->id;
    }

    public function getRua()
    {
        $mascara = \Wms\Util\Endereco::mascara();
        return str_pad($this->rua, $mascara['RUA'], "0", STR_PAD_LEFT);
    }

    public function setRua($rua)
    {
        $this->rua = $rua;
        return $this;
    }

    public function getPredio()
    {
        $mascara = \Wms\Util\Endereco::mascara();
        return str_pad($this->predio, $mascara['PREDIO'], "0", STR_PAD_LEFT);
    }

    public function setPredio($predio)
    {
        $this->predio = $predio;
        return $this;
    }

    public function getNivel()
    {
        $mascara = \Wms\Util\Endereco::mascara();
        return str_pad($this->nivel, $mascara['NIVEL'], "0", STR_PAD_LEFT);
    }

    public function setNivel($nivel)
    {
        $this->nivel = $nivel;
        return $this;
    }

    public function getApartamento()
    {
        $mascara = \Wms\Util\Endereco::mascara();
        return str_pad($this->apartamento, $mascara['APTO'], "0", STR_PAD_LEFT);
    }

    public function setApartamento($apartamento)
    {
        $this->apartamento = $apartamento;
        return $this;
    }

    public function getSituacao()
    {
        return $this->situacao;
    }

    public function setSituacao($situacao)
    {
        $this->situacao = $situacao;
        return $this;
    }

    public function getIdCaracteristica()
    {
        return $this->idCaracteristica;
    }

    public function setIdCaracteristica($idCaracteristica)
    {
        $this->idCaracteristica = $idCaracteristica;
        return $this;
    }

    public function getCaracteristica()
    {
        return $this->caracteristica;
    }

    public function setCaracteristica($caracteristica)
    {
        $this->caracteristica = $caracteristica;
        return $this;
    }

    public function getIdEstruturaArmazenagem()
    {
        return $this->idEstruturaArmazenagem;
    }

    public function setIdEstruturaArmazenagem($idEstruturaArmazenagem)
    {
        $this->idEstruturaArmazenagem = $idEstruturaArmazenagem;
        return $this;
    }

    public function getEstruturaArmazenagem()
    {
        return $this->estruturaArmazenagem;
    }

    public function setEstruturaArmazenagem($estruturaArmazenagem)
    {
        $this->estruturaArmazenagem = $estruturaArmazenagem;
        return $this;
    }

    public function getIdTipoEndereco()
    {
        return $this->idTipoEndereco;
    }

    public function setIdTipoEndereco($idTipoEndereco)
    {
        $this->idTipoEndereco = $idTipoEndereco;
        return $this;
    }

    public function getTipoEndereco()
    {
        return $this->tipoEndereco;
    }

    public function setTipoEndereco($tipoEndereco)
    {
        $this->tipoEndereco = $tipoEndereco;
        return $this;
    }

    public function getIdDeposito()
    {
        return $this->idDeposito;
    }

    public function setIdDeposito($idDeposito)
    {
        $this->idDeposito = $idDeposito;
        return $this;
    }

    public function getDeposito()
    {
        return $this->deposito;
    }

    public function setDeposito($deposito)
    {
        $this->deposito = $deposito;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getIdAreaArmazenagem()
    {
        return $this->idAreaArmazenagem;
    }

    public function setIdAreaArmazenagem($idAreaArmazenagem)
    {
        $this->idAreaArmazenagem = $idAreaArmazenagem;
        return $this;
    }

    public function getAreaArmazenagem()
    {
        return $this->areaArmazenagem;
    }

    public function setAreaArmazenagem($areaArmazenagem)
    {
        $this->areaArmazenagem = $areaArmazenagem;
        return $this;
    }

    public function getEndereco()
    {
        $rua = $this->rua;
        $predio = $this->predio;
        $nivel = $this->nivel;
        $apartamento = $this->apartamento;
        return $rua . $predio . $nivel . $apartamento;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
        return $this;
    }

    public function getEmbalagens()
    {
        return $this->embalagens;
    }

    public function getVolumes()
    {
        return $this->volumes;
    }

    /**
     * @return mixed
     */
    public function getInventarioBloqueado()
    {
        return $this->inventarioBloqueado;
    }

    /**
     * @param mixed $inventarioBloqueado
     */
    public function setInventarioBloqueado($inventarioBloqueado)
    {
        $this->inventarioBloqueado = $inventarioBloqueado;
    }

    /**
     * @return date
     */
    public function getValidade()
    {
        return $this->validade;
    }

    /**
     * @param date $validade
     */
    public function setValidade($validade)
    {
        $this->validade = $validade;
    }

}
