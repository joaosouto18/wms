<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="REGRA_PRODUTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Produto\RegraRepository")
 */
class Regra
{

    /**
     * @Column(name="COD_REGRA_PRODUTO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_REGRA_PRODUTO_01", allocationSize=1, initialValue=1)
     */
    protected $id;
    /**
     * @Column(name="DSC_REGRA_PRODUTO", type="string", length=60, nullable=false)
     */
    protected $descricao;
    /**
     * @ManyToMany(targetEntity="Wms\Domain\Entity\Produto\Tipo", mappedBy="regras")
     * 
     * @var ArrayCollection
     */
    protected $tipos;
    
    public function __construct() 
    {
        $this->tipos = new ArrayCollection();
    }
    
    public function getTipos()     
    {
	return $this->tipos;
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