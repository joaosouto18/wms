<?php

namespace Wms\Domain\Entity\Sistema;

use Doctrine\Common\Collections\ArrayCollection,
    Wms\Domain\Entity\Sistema\Recurso\Vinculo;

/**
 * Recurso
 *
 * @Table(name="RECURSO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Sistema\RecursoRepository")
 */
class Recurso
{

    /**
     * @var smallint $id
     * @Column(name="COD_RECURSO", type="smallint", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RECURSO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var string $nome
     * @Column(name="NOM_RECURSO", type="string", length=60, nullable=true)
     */
    protected $nome;

    /**
     * @var string $descricao
     * @Column(name="DSC_RECURSO", type="string", length=60, nullable=true)
     */
    protected $descricao;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Sistema\Recurso\Vinculo", mappedBy="recurso", cascade={"persist", "remove"})
     */
    protected $vinculos;

    /**
     * @var Wms\Domain\Entity\Sistema\Recurso\Mascara
     * @OneToMany(targetEntity="Wms\Domain\Entity\Sistema\Recurso\Mascara", mappedBy="recurso")
     * @JoinColumn(name="COD_RECURSO", referencedColumnName="COD_RECURSO") 
     */
    protected $mascaras;
    
    /**
     * @Column(name="COD_RECURSO_PAI", type="smallint", nullable=false)
     * @var integer
     */
    protected $idPai;

    public function __construct()
    {
        $this->vinculos = new ArrayCollection();
        $this->mascaras = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getNome()
    {
        return $this->nome;
    }

    public function setNome($nome)
    {
        $this->nome = mb_strtolower($nome, 'UTF-8');
        return $this;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
        return $this;
    }

    public function getVinculos()
    {
        return $this->vinculos;
    }

    public function setVinculos($vinculos)
    {
        $this->vinculos = $vinculos;
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
    public function getMascaras() {
        return $this->mascaras;
    }

    public function setMascaras($mascaras) {
        $this->mascaras = $mascaras;
        return $this;
    }


}