<?php
namespace Wms\Domain\Entity\Produto;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="PRODUTO_CLASSE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Produto\ClasseRepository")
 */
class Classe
{

    /**
     * @Column(name="COD_PRODUTO_CLASSE", type="string", nullable=false)
     * @Id
     * @var string
     */
    protected $id;
    /**
     * @Column(name="COD_PRODUTO_CLASSE_PAI", type="integer", nullable=false)
     */
    protected $idPai;
    /**
     * @Column(name="NOM_PRODUTO_CLASSE", type="string", length=60, nullable=false)
     */
    protected $nome;
    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Produto\Classe", mappedBy="pai")
     */
    protected $filhas;
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Classe", inversedBy="filhos")
     * @JoinColumn(name="COD_PRODUTO_CLASSE_PAI", referencedColumnName="COD_PRODUTO_CLASSE")
     */
    protected $pai;

    public function __construct()
    {
	$this->filhas = new ArrayCollection();
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

    public function getNome()
    {
	return $this->nome;
    }

    public function setNome($nome)
    {
	$this->nome = $nome;
        return $this;
    }

    /**
     * Retorna todas as classes filhas 
     * 
     * @return ArrayCollection
     */
    public function getFilhas()
    {
	return $this->filhas;
    }

    public function setFilhas($filhas)
    {
	$this->filhas = $filhas;
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

    public function getIdPai()
    {
	return $this->idPai;
    }

    public function setIdPai($idPai)
    {
	$this->idPai = $idPai;
        return $this;
    }

}