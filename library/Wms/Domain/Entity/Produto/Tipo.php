<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\Common\Collections\ArrayCollection,
    Wms\Domain\Entity\Produto as ProdutoEntity;

/**
 * @Table(name="TIPO_PRODUTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Produto\TipoRepository")
 */
class Tipo
{

    /**
     * @Column(name="COD_TIPO_PRODUTO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_TIPO_PRODUTO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DSC_TIPO_PRODUTO", type="string", length=60, nullable=false)
     */
    protected $descricao;

    /**
     * @ManyToMany(targetEntity="Wms\Domain\Entity\Produto\Regra", inversedBy="tipos")
     * @JoinTable(name="TIPO_REGRA_PRODUTO",
     *      joinColumns={@JoinColumn(name="COD_TIPO_PRODUTO", referencedColumnName="COD_TIPO_PRODUTO")},
     *      inverseJoinColumns={@JoinColumn(name="COD_REGRA_PRODUTO", referencedColumnName="COD_REGRA_PRODUTO")}
     *      )
     * @var ArrayCollection
     */
    protected $regras;

    public function __construct()
    {
        $this->regras = new ArrayCollection();
    }

    /**
     * Retorna todas as regras associadas a este tipo de produto
     * @return ArrayCollection
     */
    public function getRegras()
    {
        return $this->regras;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = mb_strtoupper($descricao, 'UTF-8');
        return $this;
    }

}