<?php

namespace Wms\Domain\Entity;

use Wms\Domain\Entity\Ator as AtorInterface;

/**
 *
 * @Table(name="EMPRESA")
 * @Entity(repositoryClass="Wms\Domain\Entity\EmpresaRepository")
 */
class Empresa implements AtorInterface
{

    /**
     * @var integer $id
     * @Column(name="COD_EMPRESA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_EMPRESA_01", initialValue=1, allocationSize=100)
     */
    protected $id;

    /**
     * @var integer Prioridade para buscar no estoque proprietario
     * @Column(name="PRIORIDADE_ESTOQUE", type="string", nullable=false)
     */
    protected $prioridadeEstoque;

    /**
     * @var string Identificação da empresa
     * @Column(name="IDENTIFICACAO", type="string", nullable=false)
     */
    protected $identificacao;

    /**
     * @var string Nome da empresa
     * @Column(name="NOM_EMPRESA", type="string", nullable=false)
     */
    protected $nomEmpresa;


    public function __construct()
    {
        $this->depositos = new \Doctrine\Common\Collections\ArrayCollection();
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

    public function setPrioridadeEstoque($prioridadeEstoque)
    {
        $this->prioridadeEstoque = $prioridadeEstoque;
    }

    public function getPrioridadeEstoque()
    {
        return $this->prioridadeEstoque;
    }

    public function getIdentificacao()
    {
        return $this->identificacao;
    }

    public function setIdentificacao($identificacao)
    {
        $this->identificacao = $identificacao;
        return $this;
    }

    public function getNomEmpresa()
    {
        return $this->nomEmpresa;
    }

    public function setNomEmpresa($nomEmpresa)
    {
        $this->nomEmpresa = $nomEmpresa;
        return $this;
    }

}