<?php

namespace Wms\Domain\Entity\Enderecamento;

/**
 *
 * @Table(name="V_SALDO_ESTOQUE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\VSaldoRepository")
 */
class VSaldo
{
    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     * @id
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     * @id
     */
    protected $grade;

    /**
     * @Column(name="COD_LINHA_SEPARACAO", type="integer", nullable=false)
     * @id
     */
    protected $codLinhaSeparacao;

    /**
     * @Column (name="DSC_LINHA_SEPARACAO", type="string", nullable=false)
     * @id
     */
    protected $dscLinhaSeparacao;

    /**
     * @Column(name="COD_DEPOSITO_ENDERECO", type="integer", nullable=false)
     * @id
     */
    protected $codEndereco;

    /**
     * @Column (name="DSC_DEPOSITO_ENDERECO", type="string", nullable=false)
     * @id
     */
    protected $dscEndereco;

    /**
     * @Column (name="QTDE", type="integer", nullable=false)
     * @id
     */
    protected $qtd;

    /**
     * @Column (name="COD_UNITIZADOR", type="string", nullable=false)
     * @id
     */
    protected $codUnitizador;
	
    /**
     * @Column (name="DSC_UNITIZADOR", type="string", nullable=false)
     * @id
     */
    protected $unitizador;
	
    /**
     * @OneToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $depositoEndereco;

    /**
     * @Column (name="VOLUME", type="string", nullable=false)
     * @id
     */
    protected $volume;

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
    public function getCodEndereco()
    {
        return $this->codEndereco;
    }

    /**
     * @param mixed $codLinhaSeparacao
     */
    public function setCodLinhaSeparacao($codLinhaSeparacao)
    {
        $this->codLinhaSeparacao = $codLinhaSeparacao;
    }

    /**
     * @return mixed
     */
    public function getCodLinhaSeparacao()
    {
        return $this->codLinhaSeparacao;
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
     * @param mixed $dscEndereco
     */
    public function setDscEndereco($dscEndereco)
    {
        $this->dscEndereco = $dscEndereco;
    }

    /**
     * @return mixed
     */
    public function getDscEndereco()
    {
        return $this->dscEndereco;
    }

    /**
     * @param mixed $dscLinhaSeparacao
     */
    public function setDscLinhaSeparacao($dscLinhaSeparacao)
    {
        $this->dscLinhaSeparacao = $dscLinhaSeparacao;
    }

    /**
     * @return mixed
     */
    public function getDscLinhaSeparacao()
    {
        return $this->dscLinhaSeparacao;
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
     * @param mixed $codUnitizador
     */
    public function setCodUnitizador($codUnitizador)
    {
        $this->codUnitizador = $codUnitizador;
    }

    /**
     * @return mixed
     */
    public function getCodUnitizador()
    {
        return $this->codUnitizador;
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
     * @param mixed $volume
     */
    public function setVolume($volume)
    {
        $this->volume = $volume;
    }

    /**
     * @return mixed
     */
    public function getVolume()
    {
        return $this->volume;
    }

}