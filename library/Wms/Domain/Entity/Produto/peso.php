<?php
namespace Wms\Domain\Entity\Produto;


/**
 * @Table(name="SUM_PESO_PRODUTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Produto\PesoRepository")
 */
class Peso
{
    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     * @var string
     */
    protected $produto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     * @var string
     */
    protected $grade;

    /**
     * @Column(name="NUM_PESO", type="string", nullable=false)
     * @var string
     */
    protected $peso;

    /**
     * @Column(name="NUM_CUBAGEM", type="string", nullable=false)
     * @var string
     */
    protected $cubagem;

    /**
     * @return string
     */
    public function getProduto()
    {
        return $this->produto;
    }

    /**
     * @param string $produto
     */
    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    /**
     * @return string
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * @param string $grade
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
    }

    /**
     * @return string
     */
    public function getPeso()
    {
        return $this->peso;
    }

    /**
     * @param string $peso
     */
    public function setPeso($peso)
    {
        $this->peso = $peso;
    }

    /**
     * @return string
     */
    public function getCubagem()
    {
        return $this->cubagem;
    }

    /**
     * @param string $cubagem
     */
    public function setCubagem($cubagem)
    {
        $this->cubagem = $cubagem;
    }

}