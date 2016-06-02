<?php
namespace Wms\Domain\Entity\Recebimento;

/**
 *
 * @Table(name="V_QTD_RECEBIMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Recebimento\VQtdRecebimentoRepository")
 */
class VQtdRecebimento
{
    /**
     * @Column(name="QTD", type="integer", nullable=false)
     */
    protected $qtd;

    /**
     * @Column(name="COD_RECEBIMENTO", type="integer", nullable=false)
     * @id
     */
    protected $codRecebimento;

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
     * @Column(name="COD_OS", type="integer", nullable=false)
     */
    protected $codOs;

    /**
     * @Column(name="COD_NORMA_PALETIZACAO", type="integer", nullable=false)
     */
    protected $codNormaPaletizacao;

    /**
     * @Column(name="NUM_PESO", type="decimal", nullable=false)
     */
    protected $peso;
    
    /**
     * @param mixed $codOs
     */
    public function setCodOs($codOs)
    {
        $this->codOs = $codOs;
    }

    /**
     * @return mixed
     */
    public function getCodOs()
    {
        return $this->codOs;
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
     * @param mixed $codRecebimento
     */
    public function setCodRecebimento($codRecebimento)
    {
        $this->codRecebimento = $codRecebimento;
    }

    /**
     * @return mixed
     */
    public function getCodRecebimento()
    {
        return $this->codRecebimento;
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
     * @return mixed
     */
    public function getPeso()
    {
        return $this->peso;
    }

    /**
     * @param mixed $peso
     */
    public function setPeso($peso)
    {
        $this->peso = $peso;
    }
    
}