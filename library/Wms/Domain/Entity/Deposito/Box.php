<?php
namespace Wms\Domain\Entity\Deposito;

/**
 * Box
 *
 * @Table(name="BOX")
 * @Entity(repositoryClass="Wms\Domain\Entity\Deposito\BoxRepository")
 */
class Box
{

    /**
     * @var string $id
     *
     * @Column(name="COD_BOX", type="string", length=5, nullable=false)
     * @Id
     */
    protected $id;
    /**
     * @var string $descricao
     *
     * @Column(name="DSC_BOX", type="string", length=60, nullable=false)
     */
    protected $descricao;
    /**
     * @var string $idPai
     *
     * @Column(name="COD_BOX_PAI", type="string", length=5, nullable=true)
     */
    protected $idPai;
    /**
     * @var smallint $idDeposito
     *
     * @Column(name="COD_DEPOSITO", type="smallint", nullable=false)
     */
    protected $idDeposito;
    /**
     * @var Wms\Domain\Entity\Deposito $deposito
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito")
     * @JoinColumn(name="COD_DEPOSITO", referencedColumnName="COD_DEPOSITO") 
     */
    protected $deposito;
    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Deposito\Box", mappedBy="pai")
     */
    protected $filhos;
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Box", inversedBy="filhos")
     * @JoinColumn(name="COD_BOX_PAI", referencedColumnName="COD_BOX")
     */
    protected $pai;


    public function __construct()
    {
	$this->filhos = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId()
    {
	return $this->id;
    }

    public function setId($id)
    {
	$this->id = $id;
        return $this;
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

    public function getIdDeposito()
    {
	return $this->idDeposito;
    }

    public function setIdDeposito($idDeposito)
    {
	$this->idDeposito = $idDeposito;
        return $this;
    }

    public function getDeposito()
    {
	return $this->deposito;
    }

    public function setDeposito($deposito)
    {
	$this->deposito = $deposito;
        return $this;
    }

    public function getIdPai()
    {
	return $this->idPai;
    }

    public function setIdPai($idPai)
    {
	$this->idPai = $idPai;
        return $this;
    }

    public function getPai()
    {
	return $this->pai;
    }

    public function setPai($pai)
    {
	$this->pai = $pai;
        return $this;
    }

}