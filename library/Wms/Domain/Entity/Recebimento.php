<?php

namespace Wms\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Wms\Domain\Entity\Recebimento\Andamento,
    Wms\Domain\Entity\NotaFiscal;

/**
 * Recebimento de carga
 *
 * @Table(name="RECEBIMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\RecebimentoRepository")
 */
class Recebimento
{

    const TIPO_SIGLA = 50;
    const STATUS_INTEGRADO = 455;
    const STATUS_CRIADO = 454;
    const STATUS_INICIADO = 456;
    const STATUS_FINALIZADO = 457;
    const STATUS_CANCELADO = 458;
    const STATUS_CONFERENCIA_CEGA = 459;
    const STATUS_DESFEITO = 460;
    const STATUS_CONFERENCIA_COLETOR = 461;

    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_RECEBIMENTO", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_RECEBIMENTO_01", initialValue=1, allocationSize=100)
     */
    protected $id;

    /**
     * Data e hora iniciou ou recebimento
     * 
     * @var datetime $dataInclusao
     * @Column(name="DTH_INICIO_RECEB", type="datetime", nullable=false)
     */
    protected $dataInicial;

    /**
     * Data e hora que finalizou o recebimento
     * 
     * @var datetime $dataInclusao
     * @Column(name="DTH_FINAL_RECEB", type="datetime", nullable=false)
     */
    protected $dataFinal;

    /**
     * @var Wms\Domain\Entity\Util\Sigla $status
     * Código da sigla do status do recebimento
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_STATUS", referencedColumnName="COD_SIGLA")
     */
    protected $status;

    /**
     * @var NotaFiscal
     * @OneToMany(targetEntity="Wms\Domain\Entity\NotaFiscal", mappedBy="recebimento", cascade={"persist", "merge"})
     */
    protected $notasFiscais;
    
    /**
     * @var ordensServicos
     * @OneToMany(targetEntity="Wms\Domain\Entity\OrdemServico", mappedBy="recebimento", cascade={"persist"})
     */
    protected $ordensServicos;

    /**
     * @var Wms\Domain\Entity\Deposito $deposito
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito")
     * @JoinColumn(name="COD_DEPOSITO", referencedColumnName="COD_DEPOSITO") 
     */
    protected $deposito;

    /**
     * @var smallint $idBox
     *
     * @Column(name="COD_BOX", type="smallint", nullable=false)
     */
    protected $idBox;

    /**
     * @var Wms\Domain\Entity\Deposito\Box $box
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Box")
     * @JoinColumn(name="COD_BOX", referencedColumnName="COD_BOX") 
     */
    protected $box;

    /**
     * @var Wms\Domain\Entity\Filial
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Filial")
     * @JoinColumn(name="COD_FILIAL", referencedColumnName="COD_FILIAL") 
     */
    protected $filial;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="Wms\Domain\Entity\Recebimento\Andamento", mappedBy="recebimento", cascade={"persist", "merge"})
     */
    protected $andamentos;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Enderecamento\Modelo")
     * @JoinColumn(name="COD_MODELO_ENDERECAMENTO", referencedColumnName="COD_MODELO_ENDERECAMENTO")
     */
    protected $modeloEnderecamento;


    /**
     * lista de tipos os status
     * @var array
     */
    public static $listaStatus = array(
        self::STATUS_INTEGRADO => 'INTEGRADO',
        self::STATUS_INICIADO => 'INICIADO',
        self::STATUS_FINALIZADO => 'FINALIZADO',
        self::STATUS_CANCELADO => 'CANCELADO',
        self::STATUS_CRIADO => 'CRIADO',
    );

    public function __construct()
    {
        $this->notasFiscais = new ArrayCollection;
        $this->andamentos = new ArrayCollection;
        $this->ordensServicos =  new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getDataInicial()
    {
        $data = $this->dataInicial;
        if ($data != null)
            return $data->format('d/m/Y');
    }

    public function setDataInicial(\DateTime $dataInicial)
    {
        $this->dataInicial = $dataInicial;
        return $this;
    }

    public function getDataFinal()
    {
        $data = $this->dataFinal;
        if ($data != null)
            return $data->format('d/m/Y à\s H:i:s');
    }

    public function setDataFinal(\DateTime $dataFinal)
    {
        $this->dataFinal = $dataFinal;
        return $this;
    }

    /**
     * Retorna todas as notas fiscais relacionadas a esse recebimento
     * 
     * @return ArrayCollection
     */
    public function getNotasFiscais()
    {
        return $this->notasFiscais;
    }
    
    public function getOrdensServicos()
    {
        return $this->ordensServicos;
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

    public function getDeposito()
    {
        return $this->deposito;
    }

    public function setDeposito($deposito)
    {
        $this->deposito = $deposito;
        return $this;
    }

    public function getAndamentos()
    {
        return $this->andamentos;
    }

    public function setAndamentos($andamentos)
    {
        $this->andamentos = $andamentos;
        return $this;
    }

    public function getStatusSigla()
    {
        return $this->statusSigla;
    }

    public function setStatusSigla($statusSigla)
    {
        $this->statusSigla = $statusSigla;
        return $this;
    }

    public function getBox()
    {
        return $this->box;
    }

    public function setBox($box)
    {
        $this->box = $box;
        return $this;
    }

    public function getIdBox()
    {
        return $this->idBox;
    }
    
    /**
     * Adiciona um andamento ao recebimento
     * @param int $statusId
     * @param int usuarioId
     */
    public function addAndamento($statusId = false, $usuarioId = false, $observacao = false)
    {
        $usuarioId = ($usuarioId) ? $usuarioId : \Zend_Auth::getInstance()->getIdentity()->getId();
        $usuario = $this->getEm()->getReference('wms:Usuario', (int) $usuarioId);

        if (!$statusId)
            $statusId = ($this->getId()) ? $this->getStatus()->getId() : self::STATUS_CRIADO;
        
        $statusEntity = $this->getEm()->getReference('wms:Util\Sigla', $statusId);
        
        $andamento = new Andamento;
        $andamento->setUsuario($usuario)
                ->setRecebimento($this)
                ->setDscObservacao($observacao)
                ->setDataAndamento(new \DateTime)
                ->setTipoAndamento($statusEntity);

        $this->andamentos[] = $andamento;
    }

    /**
     * Retorna um entityManager
     * @todo remover este método. está sendo usado temporariamente pq o Doctrine não consegue
     * persistir o objeto de usuário que vem da sessão
     * @return Doctrine\ORM\EntityManager
     */
    private function getEm()
    {
        return \Zend_Registry::get('doctrine')->getEntityManager();
    }

    public function getFilial()
    {
        return $this->filial;
    }

    public function setFilial($filial)
    {
        $this->filial = $filial;
        return $this;
    }

    /**
     * @param mixed $modeloEnderecamento
     */
    public function setModeloEnderecamento($modeloEnderecamento)
    {
        $this->modeloEnderecamento = $modeloEnderecamento;
    }

    /**
     * @return mixed
     */
    public function getModeloEnderecamento()
    {
        return $this->modeloEnderecamento;
    }

}