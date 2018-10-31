<?php

namespace Wms\Domain\Entity\Enderecamento;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Produto\Volume;

/**
 *
 * @Table(name="V_QTD_RESERVA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\VQTDReservaRepository")
 */
class VQTDReserva
{
    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     * @var string
     * @Id
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     * @var string
     * @Id
     */
    protected $grade;

    /**
     * @Column(name="COD_DEPOSITO_ENDERECO", type="integer", nullable=false)
     * @var int
     * @Id
     */
    protected $codEndereco;

    /**
     * @var int
     * @Column (name="QTD_RESERVA", type="integer", nullable=false)
     * @Id
     */
    protected $qtdReserva;
	
    /**
     * @var Produto
     * @OneToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @var Endereco
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $depositoEndereco;

    /**
     * @Column (name="VOLUME", type="string", nullable=false)
     * @var int
     * @Id
     */
    protected $codProdutoVolume;

    /**
     * @var Volume
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Volume")
     * @JoinColumn(name="VOLUME", referencedColumnName="COD_PRODUTO_VOLUME")
     */
    protected $volume;

    /**
     * @return mixed
     */
    public function getCodProduto()
    {
        return $this->codProduto;
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
    public function getGrade()
    {
        return $this->grade;
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
    public function getCodEndereco()
    {
        return $this->codEndereco;
    }

    /**
     * @param mixed $codEndereco
     */
    public function setCodEndereco($codEndereco)
    {
        $this->codEndereco = $codEndereco;
    }

    /**
     * @return mixed
     */
    public function getQtdReserva()
    {
        return $this->qtdReserva;
    }

    /**
     * @param mixed $qtdReserva
     */
    public function setQtdReserva($qtdReserva)
    {
        $this->qtdReserva = $qtdReserva;
    }

    /**
     * @return mixed
     */
    public function getProduto()
    {
        return $this->produto;
    }

    /**
     * @param mixed $produto
     */
    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    /**
     * @return Endereco
     */
    public function getDepositoEndereco()
    {
        return $this->depositoEndereco;
    }

    /**
     * @param Endereco $depositoEndereco
     */
    public function setDepositoEndereco($depositoEndereco)
    {
        $this->depositoEndereco = $depositoEndereco;
    }

    /**
     * @return int
     */
    public function getCodProdutoVolume()
    {
        return $this->codProdutoVolume;
    }

    /**
     * @param int $codProdutoVolume
     */
    public function setCodProdutoVolume($codProdutoVolume)
    {
        $this->codProdutoVolume = $codProdutoVolume;
    }

    /**
     * @return Volume
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * @param Volume $volume
     */
    public function setVolume($volume)
    {
        $this->volume = $volume;
    }

}