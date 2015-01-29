<?php

namespace Wms\Domain\Entity\Enderecamento;


/**
 * Palete
 *
 * @Table(name="PALETE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\PaleteRepository")
 */
class Palete
{
    const STATUS_EM_RECEBIMENTO = 537;
    const STATUS_RECEBIDO = 534;
    const STATUS_EM_ENDERECAMENTO = 535;
    const STATUS_ENDERECADO = 536;
    const STATUS_CANCELADO = 538;

    /**
     * U.M.A
     * @Column(name="UMA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PALETE_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Recebimento")
     * @JoinColumn(name="COD_RECEBIMENTO", referencedColumnName="COD_RECEBIMENTO")
     */
    protected $recebimento;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Armazenagem\Unitizador")
     * @JoinColumn(name="COD_UNITIZADOR", referencedColumnName="COD_UNITIZADOR")
     */
    protected $unitizador;

    /**
     * @Column(name="COD_NORMA_PALETIZACAO", type="integer",  nullable=true)
     */
    protected $codNormaPaletizacao;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;


    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     */
    protected $grade;

    /**
     * @Column(name="IND_IMPRESSO", type="string", nullable=false)
     */
    protected $impresso;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $depositoEndereco;

    /**
     * @Column(name="QTD", type="integer", nullable=false)
     */
    protected $qtd;

    /**
     * @Column(name="QTD_ENDERECADA", type="integer", nullable=false)
     */
    protected $qtdEnderecada;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_STATUS", referencedColumnName="COD_SIGLA")
     */
    protected $status;

    /**
     * @Column(name="COD_STATUS", type="integer",  nullable=true)
     */
    protected $codStatus;

    /**
     * @param mixed $codNormaPaletizacao
     */
    public function setCodNormaPaletizacao($codNormaPaletizacao)
    {
        $this->codNormaPaletizacao = $codNormaPaletizacao;
    }

    /**
     * @return mixed
     */
    public function getCodNormaPaletizacao()
    {
        return $this->codNormaPaletizacao;
    }

    /**
     * @param mixed $codProduto
     */
    public function setCodProduto($codProduto)
    {
        $this->codProduto = $codProduto;
    }

    /**
     * @return mixed
     */
    public function getCodProduto()
    {
        return $this->codProduto;
    }

    /**
     * @param mixed $codStatus
     */
    public function setCodStatus($codStatus)
    {
        $this->codStatus = $codStatus;
    }

    /**
     * @return mixed
     */
    public function getCodStatus()
    {
        return $this->codStatus;
    }

    /**
     * @param mixed $depositoEndereco
     */
    public function setDepositoEndereco($depositoEndereco)
    {
        $this->depositoEndereco = $depositoEndereco;
    }

    /**
     * @return mixed
     */
    public function getDepositoEndereco()
    {
        return $this->depositoEndereco;
    }

    /**
     * @param mixed $grade
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
    }

    /**
     * @return mixed
     */
    public function getGrade()
    {
        return $this->grade;
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $produto
     */
    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    /**
     * @return mixed
     */
    public function getProduto()
    {
        return $this->produto;
    }

    /**
     * @param mixed $qtd
     */
    public function setQtd($qtd)
    {
        $this->qtd = $qtd;
    }

    /**
     * @return mixed
     */
    public function getQtd()
    {
        return $this->qtd;
    }

    /**
     * @param mixed $recebimento
     */
    public function setRecebimento($recebimento)
    {
        $this->recebimento = $recebimento;
    }

    /**
     * @return mixed
     */
    public function getRecebimento()
    {
        return $this->recebimento;
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
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $unitizador
     */
    public function setUnitizador($unitizador)
    {
        $this->unitizador = $unitizador;
    }

    /**
     * @return mixed
     */
    public function getUnitizador()
    {
        return $this->unitizador;
    }

    /**
     * @param mixed $impresso
     */
    public function setImpresso($impresso)
    {
        $this->impresso = $impresso;
    }

    /**
     * @return mixed
     */
    public function getImpresso()
    {
        return $this->impresso;
    }

    /**
     * @param mixed $qtdEnderecada
     */
    public function setQtdEnderecada($qtdEnderecada)
    {
        $this->qtdEnderecada = $qtdEnderecada;
    }

    /**
     * @return mixed
     */
    public function getQtdEnderecada()
    {
        return $this->qtdEnderecada;
    }

}