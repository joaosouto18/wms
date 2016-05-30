<?php

namespace Wms\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Wms\Domain\Entity\NotaFiscal\Item;

/**
 * Nota fiscal
 *
 * @Table(name="NOTA_FISCAL")
 * @Entity(repositoryClass="Wms\Domain\Entity\NotaFiscalRepository")
 */
class NotaFiscal
{

    const STATUS_INTEGRADA = 15;
    const STATUS_EM_RECEBIMENTO = 16;
    const STATUS_RECEBIDA = 17;
    const STATUS_CANCELADA = 18;

    /**
     * Código da nota fiscal
     *  
     * @Id
     * @Column(name="COD_NOTA_FISCAL", type="integer", nullable=false)
     * @var integer
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_NOTA_FISCAL_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * Número da nota fiscal
     *  
     * @Column(name="NUM_NOTA_FISCAL", type="string", nullable=false)
     * @var string
     */
    protected $numero;

    /**
     * Número de série da nota fiscal
     * 
     * @Column(name="COD_SERIE_NOTA_FISCAL", type="string", length=255, nullable=false)
     * @var string
     */
    protected $serie;

    /**
     * Fornecedor da nota fiscal
     * 
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa\Papel\Fornecedor", cascade={"persist"})
     * @JoinColumn(name="COD_FORNECEDOR", referencedColumnName="COD_FORNECEDOR")
     * @var \Wms\Domain\Entity\Pessoa\Papel\Fornecedor
     */
    protected $fornecedor;

    /**
     * Data de emissão da nota fiscal
     * 
     * @var date $dataEmissao
     * @Column(name="DAT_EMISSAO", type="date", nullable=false)
     */
    protected $dataEmissao;

    /**
     * Placa do veiculo
     * 
     * @var string $placa
     * @Column(name="DSC_PLACA_VEICULO", type="string", nullable=false)
     */
    protected $placa;

    /**
     * Recebimento que contém a nota
     * 
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Recebimento", cascade={"persist"})
     * @JoinColumn(name="COD_RECEBIMENTO", referencedColumnName="COD_RECEBIMENTO")
     * @var \Wms\Domain\Entity\Recebimento
     */
    protected $recebimento;

    /**
     * Item (Produtos) da nota fiscal
     * 
     * @var Item[]
     * @OneToMany(targetEntity="Wms\Domain\Entity\NotaFiscal\Item", mappedBy="notaFiscal", cascade={"all"})
     */
    protected $itens;

    /**
     * Situação que a nota se encontra
     * 
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_STATUS", referencedColumnName="COD_SIGLA")
     */
    protected $status;
    
    /**
     * Data e hora da entrada da nota
     * 
     * @var datetime $dataEntrada
     * @Column(name="DTH_ENTRADA", type="datetime", nullable=false)
     */
    protected $dataEntrada;
    
    /**
     * @var Wms\Domain\Entity\Filial
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Filial")
     * @JoinColumn(name="COD_FILIAL", referencedColumnName="COD_FILIAL") 
     */
    protected $filial;

    /**
     * Número de série da nota fiscal
     * 
     * @Column(name="IND_BONIFICACAO", type="string", length=1, nullable=false)
     * @var string
     */
    protected $bonificacao;

    /**
     * @Column(name="DSC_OBSERVACAO", type="string", nullable=true)
     */
    protected $observacao;

    public function __construct()
    {
        $this->itens = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getNumero()
    {
        return $this->numero;
    }

    public function getPlaca()
    {
        return $this->placa;
    }

    public function setNumero($numero)
    {
        $this->numero = $numero;
        return $this;
    }

    public function setPlaca($placa)
    {
        $this->placa = $placa;
        return $this;
    }

    public function getSerie()
    {
        return $this->serie;
    }

    public function setSerie($serie)
    {
        $this->serie = $serie;
        return $this;
    }

    public function getFornecedor()
    {
        return $this->fornecedor;
    }

    public function setFornecedor($fornecedor)
    {
        $this->fornecedor = $fornecedor;
        return $this;
    }

    public function getDataEmissao()
    {
        return $this->dataEmissao;
    }

    public function setDataEmissao(\DateTime $dataEmissao)
    {
        $this->dataEmissao = $dataEmissao;
        return $this;
    }

    public function getRecebimento()
    {
        return $this->recebimento;
    }

    public function setRecebimento($recebimento = null)
    {
        $this->recebimento = $recebimento;
        return $this;
    }

    public function getItens()
    {
        return $this->itens;
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

    public function getIdFornecedor()
    {
        return $this->idFornecedor;
    }
    
    public function setDataEntrada(\DateTime $dataEntrada)
    {
        $this->dataEntrada = $dataEntrada;
        return $this;
    }

    public function getDataEntrada()
    {
        return $this->dataEntrada;
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
    
    public function getBonificacao()
    {
        return $this->bonificacao;
    }

    public function setBonificacao($bonificacao)
    {
        $this->bonificacao = $bonificacao;
        return $this;
    }

    /**
     * @param mixed $observacao
     */
    public function setObservacao($observacao)
    {
        $this->observacao = $observacao;
    }

    /**
     * @return mixed
     */
    public function getObservacao()
    {
        return $this->observacao;
    }

}