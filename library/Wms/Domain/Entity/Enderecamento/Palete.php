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
     * @Column(name="IND_IMPRESSO", type="string", nullable=false)
     */
    protected $impresso;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $depositoEndereco;

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
     * @OneToMany(targetEntity="Wms\Domain\Entity\Enderecamento\PaleteProduto", mappedBy="uma", cascade={"persist", "remove"})
     * @var ArrayCollection volumes que compoem este produto
     */
    protected $produtos;

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
     * @param \Wms\Domain\Entity\Enderecamento\ArrayCollection $produtos
     */
    public function setProdutos($produtos)
    {
        $this->produtos = $produtos;
    }

    /**
     * @return \Wms\Domain\Entity\Enderecamento\ArrayCollection
     */
    public function getProdutos()
    {
        return $this->produtos;
    }

    public function getProdutosArray() {
        $arrayProdutos = array();
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteProduto $produto */
        foreach($this->getProdutos() as $produto) {
            $arrayProduto = array();
            $arrayProduto['codProduto'] = $produto->getCodProduto();
            $arrayProduto['grade'] = $produto->getGrade();
            $arrayProduto['codProdutoEmbalagem'] = $produto->getCodProdutoEmbalagem() ;
            $arrayProduto['codProdutoVolume']  = $produto->getCodProdutoVolume();
            $arrayProduto['qtd'] = $produto->getQtd();
            $arrayProdutos[] = $arrayProduto;
        }
        return $arrayProdutos;
    }

}