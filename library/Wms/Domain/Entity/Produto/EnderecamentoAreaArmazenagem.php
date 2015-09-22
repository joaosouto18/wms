<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\Common\Collections\ArrayCollection,
    Wms\Domain\Entity\Produto;

/**
 * Description of Embalagem
 * @Table(name="PRODUTO_END_AREA_ARMAZENAGEM")
 * @Entity(repositoryClass="Wms\Domain\Entity\Produto\EnderecamentoAreaArmazenagemRepository")
 */
class EnderecamentoAreaArmazenagem
{

    /**
     * @Id
     * @Column(name="COD_PRODUTO_END_TIPO_EST_ARMAZ", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PROD_END_TIPO_EST_ARMAZ", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     * @var string Código do produto
     */
    protected $codProduto;

    /**
     * @var string Grade do produto
     * @Column(name="DSC_GRADE", type="string", length=10, nullable=false)
     */
    protected $grade;

    /**
     * @Column(name="NUM_PRIORIDADE", type="integer", length=60, nullable=false)
     * @var integer quantidade de itens esta embalagem contém
     */
    protected $prioridade;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\AreaArmazenagem")
     * @JoinColumn(name="COD_AREA_ARMAZENAGEM", referencedColumnName="COD_AREA_ARMAZENAGEM")
     */
    protected $areaArmazenagem;

    /**
     * @param mixed $areaArmazenagem
     */
    public function setAreaArmazenagem($areaArmazenagem)
    {
        $this->areaArmazenagem = $areaArmazenagem;
    }

    /**
     * @return mixed
     */
    public function getAreaArmazenagem()
    {
        return $this->areaArmazenagem;
    }

    /**
     * @param string $codProduto
     */
    public function setCodProduto($codProduto)
    {
        $this->codProduto = $codProduto;
    }

    /**
     * @return string
     */
    public function getCodProduto()
    {
        return $this->codProduto;
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
     * @param int $prioridade
     */
    public function setPrioridade($prioridade)
    {
        $this->prioridade = $prioridade;
    }

    /**
     * @return int
     */
    public function getPrioridade()
    {
        return $this->prioridade;
    }

}