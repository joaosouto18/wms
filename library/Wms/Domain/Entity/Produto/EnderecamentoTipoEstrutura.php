<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\Common\Collections\ArrayCollection,
    Wms\Domain\Entity\Produto;

/**
 * Description of Embalagem
 * @Table(name="PRODUTO_END_TIPO_EST_ARMAZ")
 * @Entity(repositoryClass="Wms\Domain\Entity\Produto\EnderecamentoTipoEstruturaRepository")
 */
class EnderecamentoTipoEstrutura
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
     * @Column(name="COD_TIPO_EST_ARMAZ", type="integer", length=60, nullable=false)
     * @var integer quantidade de itens esta embalagem contém
     */
    protected $codTipoEstrutura;

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
     * @param int $codTipoEstrutura
     */
    public function setCodTipoEstrutura($codTipoEstrutura)
    {
        $this->codTipoEstrutura = $codTipoEstrutura;
    }

    /**
     * @return int
     */
    public function getCodTipoEstrutura()
    {
        return $this->codTipoEstrutura;
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