<?php
namespace Wms\Domain\Entity\Deposito\Endereco;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="CARACTERISTICA_ENDERECO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Deposito\Endereco\CaracteristicaRepository")
 */
class Caracteristica
{
    const PICKING = 37;
    const PULMAO = 38;
    const PICKING_DINAMICO = 39;

    /**
     * @Column(name="COD_CARACTERISTICA_ENDERECO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_CARACTERISTICA_ENDERECO_01", allocationSize=1, initialValue=1)
     */
    protected $id;
    /**
     * @Column(name="DSC_CARACTERISTICA_ENDERECO", type="string", length=255, nullable=false)
     */
    protected $descricao;
     /**
     * @ManyToMany(targetEntity="Wms\Domain\Entity\Deposito\Endereco\Regra", inversedBy="caracteristicas")
     * @JoinTable(name="CARACTERISTICA_REGRA_ENDERECO",
     *      joinColumns={@JoinColumn(name="COD_CARACTERISTICA_ENDERECO", referencedColumnName="COD_CARACTERISTICA_ENDERECO")},
     *      inverseJoinColumns={@JoinColumn(name="COD_REGRA_ENDERECO", referencedColumnName="COD_REGRA_ENDERECO")}
     *      )
     * @var ArrayCollection
     */
    protected $regras;

    public function __construct()
    {
	$this->regras = new ArrayCollection();
    }

    /**
     * Retorna todas as regras associadas a este tipo de endereco
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