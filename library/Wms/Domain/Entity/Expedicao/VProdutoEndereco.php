<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="V_PRODUTO_ENDERECO")
 * @Entity()
 */
class VProdutoEndereco
{
    /**
     * @Column(name="COD_PRODUTO", type="integer", nullable=false)
     * @id
     */
    protected $codProduto;

    /**
     * @Column (name="DSC_GRADE", type="string", nullable=false)
     * @id
     */
    protected $grade;

    /**
     * @Column (name="COD_DEPOSITO_ENDERECO", type="integer", nullable=false)
     * @id
     */
    protected $codDepositoEndereco;

    /**
     * @Column(name="NUM_PREDIO", type="integer", nullable=false)
     */
    protected $predio;

    /**
     * @Column(name="NUM_APARTAMENTO", type="integer", nullable=false)
     */
    protected $apartamento;

    /**
     * @Column(name="NUM_RUA", type="integer", nullable=false)
     */
    protected $rua;

    /**
     * @Column(name="NUM_NIVEL", type="integer", nullable=false)
     */
    protected $nivel;

    /**
     * @Column (name="DSC_DEPOSITO_ENDERECO", type="string", nullable=false)
     */
    protected $endereco;

    /**
     * @param mixed $apartamento
     */
    public function setApartamento($apartamento)
    {
        $this->apartamento = $apartamento;
    }

    /**
     * @return mixed
     */
    public function getApartamento()
    {
        return $this->apartamento;
    }

    public function setCodDepositoEndereco($codDepositoEndereco)
    {
        $this->codDepositoEndereco = $codDepositoEndereco;
    }

    public function getCodDepositoEndereco()
    {
        return $this->codDepositoEndereco;
    }

    public function setCodProduto($codProduto)
    {
        $this->codProduto = $codProduto;
    }

    public function getCodProduto()
    {
        return $this->codProduto;
    }

    public function setEndereco($endereco)
    {
        $this->endereco = $endereco;
    }

    public function getEndereco()
    {
        return $this->endereco;
    }

    public function setGrade($grade)
    {
        $this->grade = $grade;
    }

    public function getGrade()
    {
        return $this->grade;
    }

    public function setNivel($nivel)
    {
        $this->nivel = $nivel;
    }

    public function getNivel()
    {
        return $this->nivel;
    }

    public function setPredio($predio)
    {
        $this->predio = $predio;
    }

    public function getPredio()
    {
        return $this->predio;
    }

    public function setRua($rua)
    {
        $this->rua = $rua;
    }

    public function getRua()
    {
        return $this->rua;
    }



}