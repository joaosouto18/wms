<?php
namespace Wms\Domain\Entity\Deposito\Endereco;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="REGRA_ENDERECO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Deposito\Endereco\RegraRepository")
 */
class Regra
{
    /**
     * @Column(name="COD_REGRA_ENDERECO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_REGRA_ENDERECO_01", allocationSize=1, initialValue=1)
     */
    protected $id;
    /**
     * @Column(name="DSC_REGRA_ENDERECO", type="string", length=255, nullable=false)
     */
    protected $descricao;
    /**
     * @ManyToMany(targetEntity="Wms\Domain\Entity\Deposito\Endereco\Caracteristica", mappedBy="regras")
     * 
     * @var ArrayCollection
     */
    protected $caracteristicas;

    public function __construct() 
    {
        $this->caracteristicas = new ArrayCollection();
    }
    
    public function getCaracteristicas()     
    {
	return $this->caracteristicas;
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