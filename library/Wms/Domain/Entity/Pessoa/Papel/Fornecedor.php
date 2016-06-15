<?php
namespace Wms\Domain\Entity\Pessoa\Papel;

use Wms\Domain\Entity\Ator;
use Wms\Domain\Entity\Pessoa;

/*
 * Fornecedor
 *
 * @Table(name="FORNECEDOR")
 * @Entity(repositoryClass="Bisna\Base\Domain\Entity\Repository")
 */
/**
 * Fornecedor
 *
 * @Table(name="FORNECEDOR")
 * @Entity(repositoryClass="Wms\Domain\Entity\Pessoa\Papel\FornecedorRepository")
 */
class Fornecedor implements Ator
{

    /*     
    * @GeneratedValue(strategy="SEQUENCE")
    * @SequenceGenerator(sequenceName="SQ_PESSOA_01", initialValue=1, allocationSize=100)
    */
    
    /**
     * @var integer $id
     * @Column(name="COD_FORNECEDOR", type="integer", nullable=false)
     * @Id
     */
    protected $id;
    /**
     * @var string $idexterno
     * @Column(name="COD_EXTERNO", type="string", nullable=false)
     */
    protected $idExterno;
    /**
     * @OneToOne(targetEntity="Wms\Domain\Entity\Pessoa\Juridica", cascade={"all"}, orphanRemoval=true)
     * @JoinColumn(name="COD_FORNECEDOR", referencedColumnName="COD_PESSOA")
     */
    protected $pessoa;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Pessoa\Juridica
     */
    public function getPessoa()
    {
        return $this->pessoa;
    }

    public function setPessoa($pessoa)
    {
        $this->pessoa = $pessoa;
        return $this;
    }

    public function getIdExterno()
    {
        return $this->idExterno;
    }

    public function setIdExterno($idExterno)
    {
        $this->idExterno = $idExterno;
        return $this;
    }

}