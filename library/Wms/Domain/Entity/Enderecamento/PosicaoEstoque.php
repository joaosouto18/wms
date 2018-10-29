<?php

namespace Wms\Domain\Entity\Enderecamento;


/**
 * PosicaoEstoque
 *
 * @Table(name="POSICAO_ESTOQUE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\PosicaoEstoqueRepository")
 */
class PosicaoEstoque
{
    /**
     * @Column(name="COD_POSICAO_ESTOQUE", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_POSICAO_ESTOQUE_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DTH_PRIMEIRA_MOVIMENTACAO", type="datetime", nullable=true)
     */
    protected $dtPrimeiraEntrada;

    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     */
    protected $grade;

    /**
     * @Column(name="COD_DEPOSITO_ENDERECO", type="integer", nullable=false)
     */
    protected $codEndereco;

    /**
     * @Column(name="QTD", type="integer", nullable=false)
     */
    protected $qtd;

    /**
     * @Column(name="COD_UNITIZADOR", type="integer", nullable=false)
     */
    protected $codUnitizador;

    /**
     * @Column(name="UMA", type="integer", nullable=false)
     */
    protected $uma;

    /**
     * @Column(name="DTH_ESTOQUE", type="datetime", nullable=true)
     */
    protected $dtEstoque;

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
     * @param mixed $dtPrimeiraEntrada
     */
    public function setDtPrimeiraEntrada($dtPrimeiraEntrada)
    {
        $this->dtPrimeiraEntrada = $dtPrimeiraEntrada;
    }

    /**
     * @return mixed
     */
    public function getDtPrimeiraEntrada()
    {
        return $this->dtPrimeiraEntrada;
    }

    /**
     * @param mixed $uma
     */
    public function setUma($uma)
    {
        $this->uma = $uma;
    }

    /**
     * @return mixed
     */
    public function getUma()
    {
        return $this->uma;
    }

    /**
     * @param mixed $dtEstoque
     */
    public function setDtEstoque($dtEstoque)
    {
        $this->dtEstoque = $dtEstoque;
    }

    /**
     * @return mixed
     */
    public function getDtEstoque()
    {
        return $this->dtEstoque;
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
    public function getCodEndereco()
    {
        return $this->codEndereco;
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


}